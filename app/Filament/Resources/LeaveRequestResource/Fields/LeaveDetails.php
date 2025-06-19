<?php

namespace App\Filament\Resources\LeaveRequestResource\Fields;

use App\Models\LeaveType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;

class LeaveDetails
{
    public static function make(): array
    {
        return [
            Section::make('LeaveDetails')
                ->schema([
                    Select::make('leave_type_id')
                        ->options(LeaveType::all()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                    DatePicker::make('start_date')
                        ->required(),
                    DatePicker::make('end_date')
                        ->required(),

                ])
                ->columns(2),
        ];
    }
}