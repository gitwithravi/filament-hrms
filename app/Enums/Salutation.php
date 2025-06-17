<?php

namespace App\Enums;

enum Salutation: string
{
    case MR = 'Mr.';
    case MRS = 'Mrs.';
    case MISS = 'Miss.';
    case DR = 'Dr.';

    public function getLabel(): string
    {
        return $this->value;
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
