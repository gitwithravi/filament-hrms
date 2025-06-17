<?php

namespace App\Filament\Resources\EmployeeCategoryResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class CategoryDetails
{
    public static function make(): array
    {
        return [
            Section::make('CategoryDetails')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(1),
        ];
    }
}