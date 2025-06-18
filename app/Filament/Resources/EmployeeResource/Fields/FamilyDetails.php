<?php

namespace App\Filament\Resources\EmployeeResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class FamilyDetails
{
    public static function make(): array
    {
        return [
            Section::make('FamilyDetails')
                ->schema([
                    TextInput::make('father_name')
                        ->maxLength(255),
                    TextInput::make('mother_name')
                        ->maxLength(255),

                ])
                ->columns(2),
        ];
    }
}