<?php

namespace App\Filament\Resources\LeaveAllocationResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Models\Employee;
use App\Services\LeaveAllocationService;

class AllocationDetails
{
    public static function make($record = null): array
    {
        return [
            Section::make('Allocation Details')
                ->schema([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->options(Employee::query()->pluck('full_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull()
                        ->live()
                        ->rules(LeaveAllocationService::getEmployeeValidationRules()['employee_id']),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull()
                        ->maxLength(1000),
                ])
                ->columns(2),
        ];
    }
}
