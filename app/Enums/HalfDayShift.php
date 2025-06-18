<?php

namespace App\Enums;

enum HalfDayShift: string
{
    case NA = 'na';
    case AN = 'an';
    case FN = 'fn';

    public function label(): string
    {
        return match($this) {
            self::NA => 'Not Applicable',
            self::AN => 'Afternoon',
            self::FN => 'Forenoon',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
