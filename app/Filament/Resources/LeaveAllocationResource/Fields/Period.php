<?php

namespace App\Filament\Resources\LeaveAllocationResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;

class Period
{
    public static function make(): array
    {
        return [
            Section::make('Period')
                ->schema([
                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required()
                        ->native(false),

                    DatePicker::make('end_date')
                        ->label('End Date')
                        ->required()
                        ->native(false)
                        ->after('start_date'),
                ])
                ->columns(2),
        ];
    }
}
