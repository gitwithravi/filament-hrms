<?php

namespace App\Enums;

enum AttendanceCategory: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case HOLIDAY = 'holiday';

    public function label(): string
    {
        return match($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::HOLIDAY => 'Holiday',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
