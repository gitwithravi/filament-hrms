<?php

namespace App\Services;

use App\Models\WorkShift;
use App\Models\Holiday;
use Carbon\Carbon;

/**
 * Working Day Service
 *
 * Handles all working day calculations including:
 * - Working day determination based on work shifts and holidays
 * - Holiday and weekoff checks
 * - Next/previous working day calculations
 * - Working days counting between date ranges
 */
class WorkingDayService
{
    /**
     * Check if a given date is a working day for the work shift
     *
     * @param Carbon $date The date to check
     * @param WorkShift $workShift The work shift to check against
     * @return bool True if it's a working day, false otherwise
     */
    public function isWorkingDay(Carbon $date, WorkShift $workShift): bool
    {
        return !$this->isWeekoff($date, $workShift) && !$this->isHoliday($date);
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
        $dayName = strtolower($date->format('l'));
        $weekoffs = $workShift->weekoffs ?? [];

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
        return Holiday::where(function ($query) use ($date) {
            $query->where('from_date', '<=', $date)
                  ->where('to_date', '>=', $date);
        })->exists();
    }

    /**
     * Get the next working day for a given work shift
     *
     * @param WorkShift $workShift The work shift to check
     * @param Carbon|null $startDate The date to start from (defaults to today)
     * @return Carbon The next working day
     */
    public function getNextWorkingDay(WorkShift $workShift, ?Carbon $startDate = null): Carbon
    {
        $currentDate = ($startDate ? $startDate->copy() : Carbon::today())->addDay();

        return $this->findWorkingDay($currentDate, $workShift, true);
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
        $currentDate = ($startDate ? $startDate->copy() : Carbon::today())->subDay();

        return $this->findWorkingDay($currentDate, $workShift, false);
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
     * Get the count of working days between two dates (excluding holidays only)
     *
     * @param Carbon|string $startDate Start date
     * @param Carbon|string $endDate End date
     * @param bool $includeWeekends Whether to include weekends as working days
     * @return int Number of working days between the dates (inclusive)
     */
    public function getWorkingDaysCount($startDate, $endDate, bool $includeWeekends = false): int
    {
        $start = $startDate instanceof Carbon ? $startDate->copy() : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate->copy() : Carbon::parse($endDate);

        if ($start->gt($end)) {
            return 0;
        }

        $workingDaysCount = 0;
        $currentDate = $start->copy();

        while ($currentDate->lte($end)) {
            if (!$this->isHoliday($currentDate)) {
                if ($includeWeekends || $currentDate->isWeekday()) {
                    $workingDaysCount++;
                }
            }
            $currentDate->addDay();
        }

        return $workingDaysCount;
    }

    /**
     * Find the next/previous working day
     *
     * @param Carbon $currentDate Starting date
     * @param WorkShift $workShift Work shift to check against
     * @param bool $forward True for next, false for previous
     * @return Carbon
     */
    private function findWorkingDay(Carbon $currentDate, WorkShift $workShift, bool $forward): Carbon
    {
        $maxIterations = 365;
        $iterations = 0;

        while ($iterations < $maxIterations) {
            if ($this->isWorkingDay($currentDate, $workShift)) {
                return $currentDate;
            }

            $forward ? $currentDate->addDay() : $currentDate->subDay();
            $iterations++;
        }

        return $currentDate;
    }
}
