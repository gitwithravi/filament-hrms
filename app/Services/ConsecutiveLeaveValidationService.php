<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Consecutive Leave Validation Service
 *
 * Handles validation of consecutive leave types to ensure
 * same leave types are used on adjacent working days
 */
class ConsecutiveLeaveValidationService
{
    public function __construct(
        private WorkingDayService $workingDayService
    ) {}

    /**
     * Check if the next working day has a different leave type applied
     *
     * @param Employee $employee
     * @param Carbon $endDate
     * @param LeaveType $leaveType
     * @param WorkShift $workShift
     * @param int|null $excludeRequestId Leave request ID to exclude (for updates)
     * @throws ValidationException
     */
    public function checkNextWorkingDayLeaveType(Employee $employee, Carbon $endDate, LeaveType $leaveType, WorkShift $workShift, ?int $excludeRequestId = null): void
    {
        $nextWorkingDay = $this->workingDayService->getNextWorkingDay($workShift, $endDate);
        $conflictingRequests = $this->getConflictingLeaveRequests($employee, $nextWorkingDay, $leaveType, $excludeRequestId);

        if ($conflictingRequests->isNotEmpty()) {
            $conflictingDetails = $this->formatConflictingDetails($conflictingRequests);

            throw ValidationException::withMessages([
                'end_date' => "The next working day ({$nextWorkingDay->format('Y-m-d')}) has a different leave type applied: {$conflictingDetails}. Consecutive leave periods must use the same leave type.",
                'leave_type_id' => "Cannot apply {$leaveType->name} as the next working day has a different leave type applied.",
            ]);
        }
    }

    /**
     * Check if the previous working day has a different leave type applied
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @param LeaveType $leaveType
     * @param WorkShift $workShift
     * @param int|null $excludeRequestId Leave request ID to exclude (for updates)
     * @throws ValidationException
     */
    public function checkPreviousWorkingDayLeaveType(Employee $employee, Carbon $startDate, LeaveType $leaveType, WorkShift $workShift, ?int $excludeRequestId = null): void
    {
        $previousWorkingDay = $this->workingDayService->getPreviousWorkingDay($workShift, $startDate);
        $conflictingRequests = $this->getConflictingLeaveRequests($employee, $previousWorkingDay, $leaveType, $excludeRequestId);

        if ($conflictingRequests->isNotEmpty()) {
            $conflictingDetails = $this->formatConflictingDetails($conflictingRequests);

            throw ValidationException::withMessages([
                'start_date' => "The previous working day ({$previousWorkingDay->format('Y-m-d')}) has a different leave type applied: {$conflictingDetails}. Consecutive leave periods must use the same leave type.",
                'leave_type_id' => "Cannot apply {$leaveType->name} as the previous working day has a different leave type applied.",
            ]);
        }
    }

    /**
     * Get conflicting leave requests for a specific date
     *
     * @param Employee $employee
     * @param Carbon $date
     * @param LeaveType $leaveType
     * @param int|null $excludeRequestId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getConflictingLeaveRequests(Employee $employee, Carbon $date, LeaveType $leaveType, ?int $excludeRequestId = null)
    {
        $dayLeaveRequests = $employee->leaveRequests()
            ->where(function ($query) use ($date) {
                $query->where('start_date', '<=', $date)
                      ->where('end_date', '>=', $date);
            })
            ->whereIn('status', ['approved', 'requested'])
            ->when($excludeRequestId, function ($query) use ($excludeRequestId) {
                $query->where('id', '!=', $excludeRequestId);
            })
            ->with('leaveType')
            ->get();

        return $dayLeaveRequests->filter(function ($request) use ($leaveType) {
            return $request->leave_type_id !== $leaveType->id;
        });
    }

    /**
     * Format conflicting request details for error messages
     *
     * @param \Illuminate\Database\Eloquent\Collection $conflictingRequests
     * @return string
     */
    private function formatConflictingDetails($conflictingRequests): string
    {
        return $conflictingRequests->map(function ($request) {
            return "{$request->leaveType->name} ({$request->start_date->format('Y-m-d')} to {$request->end_date->format('Y-m-d')}) - {$request->status->label()}";
        })->implode(', ');
    }
}