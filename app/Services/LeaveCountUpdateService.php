<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveAllocationRecord;
use App\Enums\LeaveRequestStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Leave Count Update Service
 *
 * Handles updating the 'used' count in leave_allocation_records
 * based on operations performed on LeaveRequest model
 */
class LeaveCountUpdateService
{
    public function __construct(
        private WorkingDayService $workingDayService,
        private LeaveBalanceService $leaveBalanceService
    ) {}

    /**
     * Handle leave request creation/update
     * Updates used count when leave request status changes
     *
     * @param LeaveRequest $leaveRequest
     * @param LeaveRequestStatus|null $oldStatus
     * @return void
     */
    public function handleLeaveRequestStatusChange(LeaveRequest $leaveRequest, ?LeaveRequestStatus $oldStatus = null): void
    {
        DB::transaction(function () use ($leaveRequest, $oldStatus) {
            // If old status was approved, subtract the used days first
            if ($oldStatus === LeaveRequestStatus::APPROVED) {
                $this->subtractUsedDays($leaveRequest);
            }

            // If new status is approved, add the used days
            if ($leaveRequest->status === LeaveRequestStatus::APPROVED) {
                $this->addUsedDays($leaveRequest);
            }
        });
    }

    /**
     * Handle leave request deletion
     * Subtracts used count if the deleted request was approved
     *
     * @param LeaveRequest $leaveRequest
     * @return void
     */
    public function handleLeaveRequestDeletion(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status === LeaveRequestStatus::APPROVED) {
            DB::transaction(function () use ($leaveRequest) {
                $this->subtractUsedDays($leaveRequest);
            });
        }
    }

    /**
     * Handle leave request dates update
     * Recalculates used count when dates are changed for approved requests
     *
     * @param LeaveRequest $leaveRequest
     * @param Carbon $oldStartDate
     * @param Carbon $oldEndDate
     * @param bool $oldIsHalfDay
     * @return void
     */
    public function handleLeaveRequestDatesUpdate(
        LeaveRequest $leaveRequest,
        Carbon $oldStartDate,
        Carbon $oldEndDate,
        bool $oldIsHalfDay
    ): void {
        if ($leaveRequest->status === LeaveRequestStatus::APPROVED) {
            DB::transaction(function () use ($leaveRequest, $oldStartDate, $oldEndDate, $oldIsHalfDay) {
                // Subtract old days count
                $this->subtractUsedDaysForDates($leaveRequest, $oldStartDate, $oldEndDate, $oldIsHalfDay);

                // Add new days count
                $this->addUsedDays($leaveRequest);
            });
        }
    }

    /**
     * Recalculate and sync all used counts for an employee
     * Useful for data consistency checks
     *
     * @param int $employeeId
     * @return void
     */
    public function recalculateUsedCountsForEmployee(int $employeeId): void
    {
        DB::transaction(function () use ($employeeId) {
            // Get all leave allocation records for the employee
            $leaveAllocationRecords = LeaveAllocationRecord::whereHas('leaveAllocation', function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })->get();

            foreach ($leaveAllocationRecords as $record) {
                $this->recalculateUsedCountForRecord($record);
            }
        });
    }

    /**
     * Add used days to the leave allocation record
     *
     * @param LeaveRequest $leaveRequest
     * @return void
     */
    private function addUsedDays(LeaveRequest $leaveRequest): void
    {
        $leaveDays = $this->calculateLeaveDays($leaveRequest);
        $leaveAllocationRecord = $this->findLeaveAllocationRecord($leaveRequest);

        if ($leaveAllocationRecord && $leaveDays > 0) {
            $leaveAllocationRecord->increment('used', $leaveDays);
        }
    }

    /**
     * Subtract used days from the leave allocation record
     *
     * @param LeaveRequest $leaveRequest
     * @return void
     */
    private function subtractUsedDays(LeaveRequest $leaveRequest): void
    {
        $leaveDays = $this->calculateLeaveDays($leaveRequest);
        $leaveAllocationRecord = $this->findLeaveAllocationRecord($leaveRequest);

        if ($leaveAllocationRecord && $leaveDays > 0) {
            $leaveAllocationRecord->decrement('used', $leaveDays);

            // Ensure used count doesn't go below zero
            if ($leaveAllocationRecord->used < 0) {
                $leaveAllocationRecord->update(['used' => 0]);
            }
        }
    }

    /**
     * Subtract used days for specific dates
     *
     * @param LeaveRequest $leaveRequest
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param bool $isHalfDay
     * @return void
     */
    private function subtractUsedDaysForDates(
        LeaveRequest $leaveRequest,
        Carbon $startDate,
        Carbon $endDate,
        bool $isHalfDay
    ): void {
        $leaveDays = $this->calculateLeaveDaysForDates($leaveRequest, $startDate, $endDate, $isHalfDay);
        $leaveAllocationRecord = $this->findLeaveAllocationRecord($leaveRequest);

        if ($leaveAllocationRecord && $leaveDays > 0) {
            $leaveAllocationRecord->decrement('used', $leaveDays);

            // Ensure used count doesn't go below zero
            if ($leaveAllocationRecord->used < 0) {
                $leaveAllocationRecord->update(['used' => 0]);
            }
        }
    }

    /**
     * Calculate leave days for a leave request
     *
     * @param LeaveRequest $leaveRequest
     * @return float
     */
    private function calculateLeaveDays(LeaveRequest $leaveRequest): float
    {
        return $this->calculateLeaveDaysForDates(
            $leaveRequest,
            $leaveRequest->start_date,
            $leaveRequest->end_date,
            $leaveRequest->is_half_date
        );
    }

    /**
     * Calculate leave days for specific dates
     *
     * @param LeaveRequest $leaveRequest
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param bool $isHalfDay
     * @return float
     */
    private function calculateLeaveDaysForDates(
        LeaveRequest $leaveRequest,
        Carbon $startDate,
        Carbon $endDate,
        bool $isHalfDay
    ): float {
        $employee = $leaveRequest->employee;
        $leaveType = $leaveRequest->leaveType;

        // Get employee's work shift (assuming there's a relationship)
        $workShift = $employee->workShifts()->first();

        if (!$workShift) {
            // Fallback: return simple day calculation if no work shift is defined
            return $isHalfDay ? 0.5 : $startDate->diffInDays($endDate) + 1;
        }

        return $this->leaveBalanceService->calculateRequiredLeaveDays(
            $startDate,
            $endDate,
            $workShift,
            $leaveType,
            $isHalfDay
        );
    }

    /**
     * Find the leave allocation record for the leave request
     *
     * @param LeaveRequest $leaveRequest
     * @return LeaveAllocationRecord|null
     */
    private function findLeaveAllocationRecord(LeaveRequest $leaveRequest): ?LeaveAllocationRecord
    {
        $employee = $leaveRequest->employee;
        $leaveType = $leaveRequest->leaveType;

        // Find leave allocation that covers the leave request period
        $leaveAllocation = $employee->leaveAllocations()
            ->where('start_date', '<=', $leaveRequest->start_date)
            ->where('end_date', '>=', $leaveRequest->end_date)
            ->first();

        if (!$leaveAllocation) {
            return null;
        }

        return $leaveAllocation->leaveAllocationRecords()
            ->where('leave_type_id', $leaveType->id)
            ->first();
    }

    /**
     * Recalculate used count for a specific leave allocation record
     *
     * @param LeaveAllocationRecord $record
     * @return void
     */
    private function recalculateUsedCountForRecord(LeaveAllocationRecord $record): void
    {
        $leaveAllocation = $record->leaveAllocation;
        $employee = $leaveAllocation->employee;

        // Get all approved leave requests for this employee and leave type within the allocation period
        $approvedLeaveRequests = $employee->leaveRequests()
            ->where('leave_type_id', $record->leave_type_id)
            ->where('status', LeaveRequestStatus::APPROVED)
            ->where('start_date', '>=', $leaveAllocation->start_date)
            ->where('end_date', '<=', $leaveAllocation->end_date)
            ->get();

        $totalUsedDays = 0;

        foreach ($approvedLeaveRequests as $leaveRequest) {
            $totalUsedDays += $this->calculateLeaveDays($leaveRequest);
        }

        $record->update(['used' => $totalUsedDays]);
    }
}
