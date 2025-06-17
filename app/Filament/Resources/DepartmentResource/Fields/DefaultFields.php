<?php

namespace App\Filament\Resources\DepartmentResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class DefaultFields
{
    public static function make(): array
    {
        return [
            Section::make('DefaultFields')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2),
        ];
    }
}