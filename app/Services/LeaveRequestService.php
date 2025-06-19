<?php

namespace App\Services;

use App\Models\WorkShift;
use App\Models\Holiday;
use App\Enums\WeekDay;
use Carbon\Carbon;

/**
 * Leave Request Service
 *
 * This service handles business logic for leave requests including:
 * 1. Calculating next working day based on work shift weekoffs and holidays
 * 2. Proper handling of consecutive holidays and weekoffs
 */
class LeaveRequestService
{
    /**
     * Get the next working day for a given work shift
     *
     * This function considers:
     * - Work shift weekoffs
     * - System holidays
     * - Returns the next working day based on those criteria
     *
     * @param WorkShift $workShift The work shift to check
     * @param Carbon|null $startDate The date to start from (defaults to today)
     * @return Carbon The next working day
     */
    public function getNextWorkingDay(WorkShift $workShift, ?Carbon $startDate = null): Carbon
    {
        // Start from today if no date provided, otherwise start from the provided date
        $currentDate = $startDate ? $startDate->copy() : Carbon::today();

        // Start checking from the next day
        $currentDate->addDay();

        // Maximum iterations to prevent infinite loop (check up to 365 days ahead)
        $maxIterations = 365;
        $iterations = 0;

        while ($iterations < $maxIterations) {
            // Check if current date is a working day
            if ($this->isWorkingDay($currentDate, $workShift)) {
                return $currentDate;
            }

            // Move to next day
            $currentDate->addDay();
            $iterations++;
        }

        // If we reach here, something went wrong - return the date anyway
        // This should rarely happen unless all days in a year are holidays/weekoffs
        return $currentDate;
    }

    /**
     * Check if a given date is a working day for the work shift
     *
     * @param Carbon $date The date to check
     * @param WorkShift $workShift The work shift to check against
     * @return bool True if it's a working day, false otherwise
     */
    public function isWorkingDay(Carbon $date, WorkShift $workShift): bool
    {
        // Check if it's a weekoff for this work shift
        if ($this->isWeekoff($date, $workShift)) {
            return false;
        }

        // Check if it's a holiday
        if ($this->isHoliday($date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a given date is a weekoff for the work shift
     *
     * @param Carbon $date The date to check
     * @param WorkShift $workShift The work shift to check against
     * @return bool True if it's a weekoff, false otherwise
     */
    public function isWeekoff(Carbon $date, WorkShift $workShift): bool
    {
        // Get the day name in lowercase (e.g., 'monday', 'tuesday')
        $dayName = strtolower($date->format('l'));

        // Get weekoffs from work shift
        $weekoffs = $workShift->weekoffs ?? [];

        // Check if current day is in the weekoffs array
        return in_array($dayName, $weekoffs);
    }

    /**
     * Check if a given date is a holiday
     *
     * @param Carbon $date The date to check
     * @return bool True if it's a holiday, false otherwise
     */
    public function isHoliday(Carbon $date): bool
    {
        // Check if there's any holiday that includes this date
        return Holiday::where(function ($query) use ($date) {
            $query->where('from_date', '<=', $date)
                  ->where('to_date', '>=', $date);
        })->exists();
    }

    /**
     * Get all working days between two dates for a work shift
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @param WorkShift $workShift The work shift to check against
     * @return array Array of working days
     */
    public function getWorkingDaysBetween(Carbon $startDate, Carbon $endDate, WorkShift $workShift): array
    {
        $workingDays = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            if ($this->isWorkingDay($currentDate, $workShift)) {
                $workingDays[] = $currentDate->copy();
            }
            $currentDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Count working days between two dates for a work shift
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @param WorkShift $workShift The work shift to check against
     * @return int Number of working days
     */
    public function countWorkingDaysBetween(Carbon $startDate, Carbon $endDate, WorkShift $workShift): int
    {
        return count($this->getWorkingDaysBetween($startDate, $endDate, $workShift));
    }

        /**
     * Get the previous working day for a given work shift
     *
     * @param WorkShift $workShift The work shift to check
     * @param Carbon|null $startDate The date to start from (defaults to today)
     * @return Carbon The previous working day
     */
    public function getPreviousWorkingDay(WorkShift $workShift, ?Carbon $startDate = null): Carbon
    {
        // Start from today if no date provided, otherwise start from the provided date
        $currentDate = $startDate ? $startDate->copy() : Carbon::today();

        // Start checking from the previous day
        $currentDate->subDay();

        // Maximum iterations to prevent infinite loop (check up to 365 days back)
        $maxIterations = 365;
        $iterations = 0;

        while ($iterations < $maxIterations) {
            // Check if current date is a working day
            if ($this->isWorkingDay($currentDate, $workShift)) {
                return $currentDate;
            }

            // Move to previous day
            $currentDate->subDay();
            $iterations++;
        }

        // If we reach here, something went wrong - return the date anyway
        return $currentDate;
    }

            /**
     * Get the count of working days between two dates (excluding holidays only)
     *
     * This function considers only holidays and assumes standard weekdays (Monday-Friday)
     * as working days. It does not consider work shift specific weekoffs.
     *
     * @param Carbon|string $startDate Start date
     * @param Carbon|string $endDate End date
     * @param bool $includeWeekends Whether to include weekends as working days (default: false)
     * @return int Number of working days between the dates (inclusive)
     */
    public function getWorkingDaysCount($startDate, $endDate, bool $includeWeekends = false): int
    {
        // Convert to Carbon instances if strings are passed
        $start = $startDate instanceof Carbon ? $startDate->copy() : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate->copy() : Carbon::parse($endDate);

        // Ensure start date is before or equal to end date
        if ($start->gt($end)) {
            return 0;
        }

        $workingDaysCount = 0;
        $currentDate = $start->copy();

        while ($currentDate->lte($end)) {
            // Check if it's a holiday
            if (!$this->isHoliday($currentDate)) {
                // If we're including weekends, count all non-holiday days
                if ($includeWeekends) {
                    $workingDaysCount++;
                } else {
                    // Only count weekdays (Monday-Friday)
                    if ($currentDate->isWeekday()) {
                        $workingDaysCount++;
                    }
                }
            }

            $currentDate->addDay();
        }

        return $workingDaysCount;
    }
}
