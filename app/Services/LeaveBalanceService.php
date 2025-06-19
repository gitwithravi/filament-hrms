<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\WorkShift;
use Carbon\Carbon;

/**
 * Leave Balance Service
 *
 * Handles leave balance calculations and leave day requirements
 */
class LeaveBalanceService
{
    public function __construct(
        private WorkingDayService $workingDayService
    ) {}

    /**
     * Get available leave balance for an employee for a specific leave type
     *
     * @param Employee $employee
     * @param LeaveType $leaveType
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function getAvailableLeaveBalance(Employee $employee, LeaveType $leaveType, Carbon $startDate, Carbon $endDate): float
    {
        $leaveAllocation = $this->findLeaveAllocation($employee, $startDate, $endDate);

        if (!$leaveAllocation) {
            return 0;
        }

        $leaveAllocationRecord = $leaveAllocation->leaveAllocationRecords()
            ->where('leave_type_id', $leaveType->id)
            ->first();

        return $leaveAllocationRecord?->remaining ?? 0;
    }

    /**
     * Calculate required leave days based on leave type and work shift
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param WorkShift $workShift
     * @param LeaveType $leaveType
     * @param bool $isHalfDay
     * @return float
     */
    public function calculateRequiredLeaveDays(
        Carbon $startDate,
        Carbon $endDate,
        WorkShift $workShift,
        LeaveType $leaveType,
        bool $isHalfDay = false
    ): float {
        if ($isHalfDay) {
            return 0.5;
        }

        if ($leaveType->is_sandwich_type) {
            return $startDate->diffInDays($endDate) + 1;
        }

        return $this->workingDayService->countWorkingDaysBetween($startDate, $endDate, $workShift);
    }

    /**
     * Find leave allocation that covers the requested period
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \App\Models\LeaveAllocation|null
     */
    private function findLeaveAllocation(Employee $employee, Carbon $startDate, Carbon $endDate)
    {
        return $employee->leaveAllocations()
            ->where('start_date', '<=', $startDate)
            ->where('end_date', '>=', $endDate)
            ->first();
    }
}
