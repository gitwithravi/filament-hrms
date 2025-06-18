<?php

namespace App\Filament\Resources\LeaveAllocationResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use App\Filament\Resources\LeaveAllocationResource\Rules;

class Period
{
    public static function make($record = null): array
    {
        return [
            Section::make('Period')
                ->schema([
                                                            DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required()
                        ->native(false)
                        ->live(onBlur: true)
                        ->rules([
                            function (Get $get) use ($record) {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get, $record) {
                                    $employeeId = $get('employee_id');
                                    $endDate = $get('end_date');

                                    if (!$employeeId || !$value || !$endDate) {
                                        return;
                                    }

                                    if (Rules::hasOverlappingAllocation($employeeId, $value, $endDate, $record?->id)) {
                                        $fail('This employee already has a leave allocation that overlaps with the selected period.');
                                    }
                                };
                            },
                        ]),

                                        DatePicker::make('end_date')
                        ->label('End Date')
                        ->required()
                        ->native(false)
                        ->after('start_date')
                        ->live(onBlur: true)
                        ->rules([
                            function (Get $get) use ($record) {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get, $record) {
                                    $employeeId = $get('employee_id');
                                    $startDate = $get('start_date');

                                    if (!$employeeId || !$startDate || !$value) {
                                        return;
                                    }

                                    if (Rules::hasOverlappingAllocation($employeeId, $startDate, $value, $record?->id)) {
                                        $fail('This employee already has a leave allocation that overlaps with the selected period.');
                                    }
                                };
                            },
                        ]),
                ])
                ->columns(2),
        ];
    }
}
