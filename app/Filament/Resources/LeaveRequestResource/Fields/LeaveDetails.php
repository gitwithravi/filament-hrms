<?php

namespace App\Filament\Resources\LeaveRequestResource\Fields;

use Filament\Forms\Get;
use App\Models\LeaveType;
use Filament\Forms\Components\Radio;
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
                    Radio::make('is_half_date')
                        ->options([
                            '1' => 'Yes',
                            '0' => 'No',
                        ])
                        ->inline()
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    Radio::make('half_day_shift')
                        ->options([
                            'fn' => 'First Half',
                            'an' => 'Second Half',
                        ])
                        ->inline()
                        ->required()
                        ->visible(fn (Get $get) => $get('is_half_date') == 1)
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