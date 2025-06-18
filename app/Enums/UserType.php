<?php

namespace App\Enums;

enum UserType: string
{
    case EMPLOYEE = 'employee';
    case MANAGER = 'manager';
    case USER = 'user';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
