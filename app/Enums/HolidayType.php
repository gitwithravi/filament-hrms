<?php

namespace App\Enums;

enum HolidayType: string
{
    case GLOBAL = 'global';
    case SECTIONAL = 'sectional';

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get enum labels for display
     */
    public function label(): string
    {
        return match($this) {
            self::GLOBAL => 'Global',
            self::SECTIONAL => 'Sectional',
        };
    }

    /**
     * Get all enum options for forms
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();
    }
}
