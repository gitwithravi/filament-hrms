<?php

namespace App\Filament\Resources\HolidayResource\Fields;

use App\Enums\HolidayType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class HolidayDetails
{
    public static function make(): array
    {
        return [
            Section::make('Holiday Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    DatePicker::make('from_date')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    DatePicker::make('to_date')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('from_date'),
                    Select::make('holiday_type')
                        ->options(HolidayType::options())
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}
