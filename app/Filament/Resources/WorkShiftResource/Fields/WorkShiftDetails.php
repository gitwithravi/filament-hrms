<?php

namespace App\Filament\Resources\WorkShiftResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use App\Enums\WeekDay;

class WorkShiftDetails
{
    public static function make(): array
    {
        return [
            Section::make('WorkShiftDetails')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('description')
                        ->required()
                        ->maxLength(255),
                    TimePicker::make('start_time')
                        ->required(),
                    TimePicker::make('end_time')
                        ->required(),
                    Select::make('weekoffs')
                        ->options(WeekDay::class)
                        ->required()
                        ->multiple(),
                ])
                ->columns(2),
        ];
    }
}