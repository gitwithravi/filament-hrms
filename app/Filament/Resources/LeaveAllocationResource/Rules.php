<?php

namespace App\Filament\Resources\LeaveAllocationResource;

use App\Models\LeaveAllocation;
use Illuminate\Validation\Rule;
use Closure;

/**
 * Custom validation rules for Leave Allocation Resource
 *
 * This class handles validation to ensure that:
 * 1. No two leave allocations for the same employee overlap in time periods
 * 2. Proper date range validation (allows past dates)
 * 3. Employee selection validation
 *
 * Overlap Logic:
 * Two date periods overlap if: start1 <= end2 AND start2 <= end1
 * This covers all possible overlap scenarios:
 * - Period A starts before B but ends during B
 * - Period A starts during B
 * - Period A completely contains B
 * - Period B completely contains A
 */
class Rules
{
    /**
     * Get validation rules for leave allocation overlap checking
     */
    public static function getOverlapValidationRules(?LeaveAllocation $record = null): array
    {
        return [
            'employee_id' => [
                'required',
                'exists:employees,id',
            ],
            'start_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail) use ($record) {
                    $employeeId = request()->input('employee_id');
                    $endDate = request()->input('end_date');

                    if (!$employeeId || !$value || !$endDate) {
                        return;
                    }

                    if (self::hasOverlappingAllocation($employeeId, $value, $endDate, $record?->id)) {
                        $fail('This employee already has a leave allocation that overlaps with the selected period.');
                    }
                },
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                function (string $attribute, mixed $value, Closure $fail) use ($record) {
                    $employeeId = request()->input('employee_id');
                    $startDate = request()->input('start_date');

                    if (!$employeeId || !$startDate || !$value) {
                        return;
                    }

                    if (self::hasOverlappingAllocation($employeeId, $startDate, $value, $record?->id)) {
                        $fail('This employee already has a leave allocation that overlaps with the selected period.');
                    }
                },
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Check if there's an overlapping leave allocation for the employee
     */
    public static function hasOverlappingAllocation(int $employeeId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        try {
            $query = LeaveAllocation::where('employee_id', $employeeId)
                ->where(function ($query) use ($startDate, $endDate) {
                    // Check for any overlap using the overlap condition:
                    // Two periods overlap if: start1 <= end2 AND start2 <= end1
                    $query->where(function ($subQuery) use ($startDate, $endDate) {
                        // Case 1: New period starts before existing ends AND existing starts before new ends
                        $subQuery->where('start_date', '<=', $endDate)
                                 ->where('end_date', '>=', $startDate);
                    });
                });

            // Exclude current record when editing
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            return $query->exists();
        } catch (\Exception $e) {
            // Log the error and return false to allow the form to proceed
            \Log::error('Error checking leave allocation overlap: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get overlapping allocations for debugging/display purposes
     */
    public static function getOverlappingAllocations(int $employeeId, string $startDate, string $endDate, ?int $excludeId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = LeaveAllocation::where('employee_id', $employeeId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->where('start_date', '<=', $endDate)
                             ->where('end_date', '>=', $startDate);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    /**
     * Get validation rules for employee selection with custom message
     */
    public static function getEmployeeValidationRules(): array
    {
        return [
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id'),
            ],
        ];
    }

    /**
     * Get validation rules for date period
     */
    public static function getDatePeriodValidationRules(): array
    {
        return [
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],
        ];
    }

    /**
     * Custom validation messages
     */
    public static function getValidationMessages(): array
    {
        return [
            'employee_id.required' => 'Please select an employee.',
            'employee_id.exists' => 'The selected employee is invalid.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.required' => 'End date is required.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after' => 'End date must be after the start date.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get all validation rules combined
     */
    public static function getAllValidationRules(?LeaveAllocation $record = null): array
    {
        return self::getOverlapValidationRules($record);
    }
}
