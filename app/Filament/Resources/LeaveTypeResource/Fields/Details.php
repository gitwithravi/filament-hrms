<?php

namespace App\Filament\Resources\LeaveTypeResource\Fields;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class Details
{
    public static function make(): array
    {
        return [
            Section::make('Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('code')
                        ->required()
                        ->maxLength(2),
                    TextInput::make('yearly_grant')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    Toggle::make('is_sandwich_type')
                        ->label('Is Sandwich Type')
                        ->default(false)
                        ->inline(),
                ])
                ->columns(2),
        ];
    }
}