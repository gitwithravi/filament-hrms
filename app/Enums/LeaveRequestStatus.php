<?php

namespace App\Enums;

enum LeaveRequestStatus: string
{
    case APPROVED = 'approved';
    case REQUESTED = 'requested';
    case WITHDRAWN = 'withdrawn';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::APPROVED => 'Approved',
            self::REQUESTED => 'Requested',
            self::WITHDRAWN => 'Withdrawn',
            self::REJECTED => 'Rejected',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}