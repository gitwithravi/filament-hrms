<?php

namespace App\Enums;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHERS = 'others';

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
