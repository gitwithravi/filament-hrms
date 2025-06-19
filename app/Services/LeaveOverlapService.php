<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Leave Overlap Service
 *
 * Handles overlapping leave request detection and validation
 */
class LeaveOverlapService
{
    /**
     * Check for overlapping leave requests for an employee
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $excludeRequestId Leave request ID to exclude (for updates)
     * @throws ValidationException
     */
    public function checkOverlappingLeaveRequests(Employee $employee, Carbon $startDate, Carbon $endDate, ?int $excludeRequestId = null): void
    {
        $overlappingRequests = $this->getOverlappingRequests($employee, $startDate, $endDate, $excludeRequestId);

        if ($overlappingRequests->isNotEmpty()) {
            $overlappingDetails = $this->formatOverlappingDetails($overlappingRequests);

            throw ValidationException::withMessages([
                'start_date' => "Leave request overlaps with existing leave(s): {$overlappingDetails}",
                'end_date' => "Leave request overlaps with existing leave(s): {$overlappingDetails}",
            ]);
        }
    }

    /**
     * Check if there are overlapping leave requests (returns boolean)
     *
     * @param int $employeeId
     * @param Carbon|string $startDate
     * @param Carbon|string $endDate
     * @param int|null $excludeRequestId
     * @return bool
     */
    public function hasOverlappingLeaveRequests(int $employeeId, $startDate, $endDate, ?int $excludeRequestId = null): bool
    {
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return false;
        }

        try {
            $this->checkOverlappingLeaveRequests($employee, $startDate, $endDate, $excludeRequestId);
            return false;
        } catch (ValidationException $e) {
            return true;
        }
    }

    /**
     * Get overlapping leave requests for an employee (for display purposes)
     *
     * @param int $employeeId
     * @param Carbon|string $startDate
     * @param Carbon|string $endDate
     * @param int|null $excludeRequestId
     * @return Collection
     */
    public function getOverlappingLeaveRequests(int $employeeId, $startDate, $endDate, ?int $excludeRequestId = null): Collection
    {
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return collect();
        }

        return $this->getOverlappingRequests($employee, $startDate, $endDate, $excludeRequestId);
    }

    /**
     * Get overlapping leave requests from database
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $excludeRequestId
     * @return Collection
     */
    private function getOverlappingRequests(Employee $employee, Carbon $startDate, Carbon $endDate, ?int $excludeRequestId = null): Collection
    {
        return $employee->leaveRequests()
            ->where(function ($query) use ($startDate, $endDate) {
                // Two periods overlap if: start1 <= end2 AND start2 <= end1
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })
            ->whereIn('status', ['approved', 'requested'])
            ->when($excludeRequestId, function ($query) use ($excludeRequestId) {
                $query->where('id', '!=', $excludeRequestId);
            })
            ->with('leaveType')
            ->get();
    }

    /**
     * Format overlapping request details for error messages
     *
     * @param Collection $overlappingRequests
     * @return string
     */
    private function formatOverlappingDetails(Collection $overlappingRequests): string
    {
        return $overlappingRequests->map(function ($request) {
            return "{$request->leaveType->name} ({$request->start_date->format('Y-m-d')} to {$request->end_date->format('Y-m-d')}) - {$request->status->label()}";
        })->implode(', ');
    }
}
