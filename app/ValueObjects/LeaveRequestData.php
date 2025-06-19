<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * Leave Request Data Value Object
 *
 * Encapsulates leave request data with validation and type safety
 */
readonly class LeaveRequestData
{
    public function __construct(
        public ?int $employeeId,
        public ?int $leaveTypeId,
        public ?Carbon $startDate,
        public ?Carbon $endDate,
        public bool $isHalfDay = false,
        public ?int $excludeRequestId = null
    ) {}

    /**
     * Create from array data
     *
     * @param array $data
     * @param int|null $excludeRequestId
     * @return self
     */
    public static function fromArray(array $data, ?int $excludeRequestId = null): self
    {
        return new self(
            employeeId: $data['employee_id'] ?? null,
            leaveTypeId: $data['leave_type_id'] ?? null,
            startDate: isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            endDate: isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            isHalfDay: ($data['is_half_date'] ?? false) == '1' || ($data['is_half_date'] ?? false) === true,
            excludeRequestId: $excludeRequestId
        );
    }

    /**
     * Check if all required fields are present
     *
     * @return bool
     */
    public function hasRequiredFields(): bool
    {
        return $this->employeeId &&
               $this->leaveTypeId &&
               $this->startDate &&
               $this->endDate;
    }

    /**
     * Check if dates are logically valid
     *
     * @return bool
     */
    public function hasValidDates(): bool
    {
        return $this->startDate && $this->endDate && $this->startDate->lte($this->endDate);
    }

    /**
     * Check if half day configuration is valid
     *
     * @return bool
     */
    public function hasValidHalfDayConfig(): bool
    {
        if (!$this->isHalfDay) {
            return true;
        }

        return $this->startDate && $this->endDate && $this->startDate->isSameDay($this->endDate);
    }
}
