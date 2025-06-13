<?php

namespace App\Filament\Resources\DepartmentResource\Fields;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;

class InfoFields
{
    public static function make(): array
    {
        return [
            Section::make('InfoFields')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required(),

                    // Add more fields here as needed
                ])
                ->columns(1),
        ];
    }
}