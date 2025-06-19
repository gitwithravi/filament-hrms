<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use App\ValueObjects\LeaveRequestData;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Leave Request Service
 *
 * Main coordinator service for leave request operations.
 * Delegates specific responsibilities to specialized services:
 * - WorkingDayService: Working day calculations
 * - LeaveBalanceService: Leave balance calculations
 * - LeaveOverlapService: Overlapping leave detection
 * - ConsecutiveLeaveValidationService: Consecutive leave type validation
 */
class LeaveRequestService
{
    public function __construct(
        private WorkingDayService $workingDayService,
        private LeaveBalanceService $leaveBalanceService,
        private LeaveOverlapService $leaveOverlapService,
        private ConsecutiveLeaveValidationService $consecutiveLeaveValidationService
    ) {}
    /**
     * Validate leave request data
     *
     * @param array $data Leave request data
     * @param int|null $excludeRequestId Leave request ID to exclude (for updates)
     * @return array Validation result with success status and messages
     * @throws ValidationException
     */
    public function validateLeaveRequest(array $data, ?int $excludeRequestId = null): array
    {
        $leaveRequestData = $this->prepareLeaveRequestData($data, $excludeRequestId);

        $this->validateBasicData($leaveRequestData);

        $employee = $this->getEmployeeOrFail($leaveRequestData->employeeId);
        $leaveType = $this->getLeaveTypeOrFail($leaveRequestData->leaveTypeId);

        // Check for overlapping leave requests
        $this->leaveOverlapService->checkOverlappingLeaveRequests(
            $employee,
            $leaveRequestData->startDate,
            $leaveRequestData->endDate,
            $excludeRequestId
        );

        $workShift = $this->getWorkShiftOrFail($employee, $leaveRequestData->startDate);

        // Check consecutive leave type validation
        $this->consecutiveLeaveValidationService->checkNextWorkingDayLeaveType(
            $employee, $leaveRequestData->endDate, $leaveType, $workShift, $excludeRequestId
        );

        $this->consecutiveLeaveValidationService->checkPreviousWorkingDayLeaveType(
            $employee, $leaveRequestData->startDate, $leaveType, $workShift, $excludeRequestId
        );

        // Calculate and validate leave balance
        $requiredLeaveDays = $this->leaveBalanceService->calculateRequiredLeaveDays(
            $leaveRequestData->startDate,
            $leaveRequestData->endDate,
            $workShift,
            $leaveType,
            $leaveRequestData->isHalfDay
        );

        $availableLeave = $this->leaveBalanceService->getAvailableLeaveBalance(
            $employee, $leaveType, $leaveRequestData->startDate, $leaveRequestData->endDate
        );

        if ($requiredLeaveDays > $availableLeave) {
            throw ValidationException::withMessages([
                'leave_type_id' => "Insufficient leave balance. Required: {$requiredLeaveDays} days, Available: {$availableLeave} days.",
            ]);
        }

        return [
            'success' => true,
            'required_days' => $requiredLeaveDays,
            'available_days' => $availableLeave,
            'message' => 'Leave request validation passed.'
        ];
    }

    /**
     * Prepare leave request data from array
     *
     * @param array $data
     * @param int|null $excludeRequestId
     * @return LeaveRequestData
     */
    private function prepareLeaveRequestData(array $data, ?int $excludeRequestId = null): LeaveRequestData
    {
        $leaveRequestData = LeaveRequestData::fromArray($data, $excludeRequestId);

        // Try to resolve employee from current user if not provided
        if (!$leaveRequestData->employeeId) {
            $leaveRequestData = new LeaveRequestData(
                employeeId: $this->resolveEmployeeFromCurrentUser(),
                leaveTypeId: $leaveRequestData->leaveTypeId,
                startDate: $leaveRequestData->startDate,
                endDate: $leaveRequestData->endDate,
                isHalfDay: $leaveRequestData->isHalfDay,
                excludeRequestId: $leaveRequestData->excludeRequestId
            );
        }

        return $leaveRequestData;
    }

    /**
     * Resolve employee ID from current authenticated user
     *
     * @return int|null
     */
    private function resolveEmployeeFromCurrentUser(): ?int
    {
        $userId = auth()->user()->id ?? null;
        if (!$userId) {
            return null;
        }

        return Employee::where('user_id', $userId)->value('id');
    }

    /**
     * Validate basic leave request data
     *
     * @param LeaveRequestData $data
     * @throws ValidationException
     */
    private function validateBasicData(LeaveRequestData $data): void
    {
        if (!$data->employeeId) {
            throw ValidationException::withMessages([
                'employee_id' => 'Employee ID is required for validation.',
            ]);
        }

        if (!$data->hasRequiredFields()) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'Leave type is required.',
                'start_date' => 'Start date and end date are required.',
                'end_date' => 'Start date and end date are required.',
            ]);
        }

        if (!$data->hasValidDates()) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be greater than or equal to start date.',
            ]);
        }

        if (!$data->hasValidHalfDayConfig()) {
            throw ValidationException::withMessages([
                'is_half_date' => 'For half day leave, start date and end date must be the same.',
                'end_date' => 'For half day leave, start date and end date must be the same.',
            ]);
        }
    }

    /**
     * Get employee or throw validation exception
     *
     * @param int $employeeId
     * @return Employee
     * @throws ValidationException
     */
    private function getEmployeeOrFail(int $employeeId): Employee
    {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'Employee not found.',
            ]);
        }

        return $employee;
    }

    /**
     * Get leave type or throw validation exception
     *
     * @param int $leaveTypeId
     * @return LeaveType
     * @throws ValidationException
     */
    private function getLeaveTypeOrFail(int $leaveTypeId): LeaveType
    {
        $leaveType = LeaveType::find($leaveTypeId);

        if (!$leaveType) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'Leave type not found.',
            ]);
        }

        return $leaveType;
    }

    /**
     * Get work shift or throw validation exception
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @return \App\Models\WorkShift
     * @throws ValidationException
     */
    private function getWorkShiftOrFail(Employee $employee, Carbon $startDate)
    {
        $workShift = $employee->workShifts()
            ->wherePivot('end_date', '>=', $startDate)
            ->orWherePivotNull('end_date')
            ->orderByPivot('start_date', 'desc')
            ->first();

        if (!$workShift) {
            throw ValidationException::withMessages([
                'employee_id' => 'No work shift found for this employee for the requested dates.',
            ]);
        }

        return $workShift;
    }

    /**
     * Check if there are overlapping leave requests (public method for external use)
     *
     * @param int $employeeId
     * @param Carbon|string $startDate
     * @param Carbon|string $endDate
     * @param int|null $excludeRequestId
     * @return bool
     */
    public function hasOverlappingLeaveRequests(int $employeeId, $startDate, $endDate, ?int $excludeRequestId = null): bool
    {
        return $this->leaveOverlapService->hasOverlappingLeaveRequests($employeeId, $startDate, $endDate, $excludeRequestId);
    }

    /**
     * Get overlapping leave requests for an employee (for display purposes)
     *
     * @param int $employeeId
     * @param Carbon|string $startDate
     * @param Carbon|string $endDate
     * @param int|null $excludeRequestId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverlappingLeaveRequests(int $employeeId, $startDate, $endDate, ?int $excludeRequestId = null)
    {
        return $this->leaveOverlapService->getOverlappingLeaveRequests($employeeId, $startDate, $endDate, $excludeRequestId);
    }

    /**
     * Get the next working day for a given work shift
     *
     * @param \App\Models\WorkShift $workShift The work shift to check
     * @param Carbon|null $startDate The date to start from (defaults to today)
     * @return Carbon The next working day
     */
    public function getNextWorkingDay($workShift, ?Carbon $startDate = null): Carbon
    {
        return $this->workingDayService->getNextWorkingDay($workShift, $startDate);
    }

    /**
     * Check if a given date is a working day for the work shift
     *
     * @param Carbon $date The date to check
     * @param \App\Models\WorkShift $workShift The work shift to check against
     * @return bool True if it's a working day, false otherwise
     */
    public function isWorkingDay(Carbon $date, $workShift): bool
    {
        return $this->workingDayService->isWorkingDay($date, $workShift);
    }

    /**
     * Check if a given date is a weekoff for the work shift
     *
     * @param Carbon $date The date to check
     * @param \App\Models\WorkShift $workShift The work shift to check against
     * @return bool True if it's a weekoff, false otherwise
     */
    public function isWeekoff(Carbon $date, $workShift): bool
    {
        return $this->workingDayService->isWeekoff($date, $workShift);
    }

    /**
     * Check if a given date is a holiday
     *
     * @param Carbon $date The date to check
     * @return bool True if it's a holiday, false otherwise
     */
    public function isHoliday(Carbon $date): bool
    {
        return $this->workingDayService->isHoliday($date);
    }

    /**
     * Get all working days between two dates for a work shift
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @param \App\Models\WorkShift $workShift The work shift to check against
     * @return array Array of working days
     */
    public function getWorkingDaysBetween(Carbon $startDate, Carbon $endDate, $workShift): array
    {
        return $this->workingDayService->getWorkingDaysBetween($startDate, $endDate, $workShift);
    }

    /**
     * Count working days between two dates for a work shift
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @param \App\Models\WorkShift $workShift The work shift to check against
     * @return int Number of working days
     */
    public function countWorkingDaysBetween(Carbon $startDate, Carbon $endDate, $workShift): int
    {
        return $this->workingDayService->countWorkingDaysBetween($startDate, $endDate, $workShift);
    }

    /**
     * Get the previous working day for a given work shift
     *
     * @param \App\Models\WorkShift $workShift The work shift to check
     * @param Carbon|null $startDate The date to start from (defaults to today)
     * @return Carbon The previous working day
     */
    public function getPreviousWorkingDay($workShift, ?Carbon $startDate = null): Carbon
    {
        return $this->workingDayService->getPreviousWorkingDay($workShift, $startDate);
    }

    /**
     * Get the count of working days between two dates (excluding holidays only)
     *
     * @param Carbon|string $startDate Start date
     * @param Carbon|string $endDate End date
     * @param bool $includeWeekends Whether to include weekends as working days
     * @return int Number of working days between the dates (inclusive)
     */
    public function getWorkingDaysCount($startDate, $endDate, bool $includeWeekends = false): int
    {
        return $this->workingDayService->getWorkingDaysCount($startDate, $endDate, $includeWeekends);
    }

    /**
     * Validate leave request for live form validation (partial validation)
     *
     * @param array $data Partial leave request data
     * @param int|null $excludeRequestId Leave request ID to exclude (for updates)
     * @return array Validation result with success status and messages
     */
    public function validateLeaveRequestPartial(array $data, ?int $excludeRequestId = null): array
    {
        try {
            $leaveRequestData = $this->prepareLeaveRequestData($data, $excludeRequestId);

            // If we don't have enough data, return early
            if (!$leaveRequestData->hasRequiredFields()) {
                return [
                    'success' => false,
                    'message' => 'Insufficient data for validation.'
                ];
            }

            if (!$leaveRequestData->hasValidDates()) {
                return [
                    'success' => false,
                    'message' => 'End date must be greater than or equal to start date.'
                ];
            }

            if (!$leaveRequestData->hasValidHalfDayConfig()) {
                return [
                    'success' => false,
                    'message' => 'For half day leave, start date and end date must be the same.'
                ];
            }

            $employee = Employee::find($leaveRequestData->employeeId);
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => 'Employee not found.'
                ];
            }

            $leaveType = LeaveType::find($leaveRequestData->leaveTypeId);
            if (!$leaveType) {
                return [
                    'success' => false,
                    'message' => 'Leave type not found.'
                ];
            }

            // Check for overlapping leave requests
            $overlappingRequests = $this->leaveOverlapService->getOverlappingLeaveRequests(
                $leaveRequestData->employeeId,
                $leaveRequestData->startDate,
                $leaveRequestData->endDate,
                $excludeRequestId
            );

            if ($overlappingRequests->isNotEmpty()) {
                $overlappingDetails = $overlappingRequests->map(function ($request) {
                    return "{$request->leaveType->name} ({$request->start_date->format('Y-m-d')} to {$request->end_date->format('Y-m-d')}) - {$request->status->label()}";
                })->implode(', ');

                return [
                    'success' => false,
                    'message' => "Overlapping leave found: {$overlappingDetails}"
                ];
            }

            $workShift = $this->getWorkShiftOrFail($employee, $leaveRequestData->startDate);

            // Check consecutive leave type validation
            try {
                $this->consecutiveLeaveValidationService->checkNextWorkingDayLeaveType(
                    $employee, $leaveRequestData->endDate, $leaveType, $workShift, $excludeRequestId
                );

                $this->consecutiveLeaveValidationService->checkPreviousWorkingDayLeaveType(
                    $employee, $leaveRequestData->startDate, $leaveType, $workShift, $excludeRequestId
                );
            } catch (ValidationException $e) {
                $errors = collect($e->errors())->flatten()->first();
                return [
                    'success' => false,
                    'message' => $errors
                ];
            }

            $requiredLeaveDays = $this->leaveBalanceService->calculateRequiredLeaveDays(
                $leaveRequestData->startDate,
                $leaveRequestData->endDate,
                $workShift,
                $leaveType,
                $leaveRequestData->isHalfDay
            );

            $availableLeave = $this->leaveBalanceService->getAvailableLeaveBalance(
                $employee, $leaveType, $leaveRequestData->startDate, $leaveRequestData->endDate
            );

            if ($requiredLeaveDays > $availableLeave) {
                return [
                    'success' => false,
                    'message' => "Insufficient leave balance. Required: {$requiredLeaveDays} days, Available: {$availableLeave} days."
                ];
            }

            return [
                'success' => true,
                'required_days' => $requiredLeaveDays,
                'available_days' => $availableLeave,
                'message' => 'Validation passed.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ];
        }
    }

}
