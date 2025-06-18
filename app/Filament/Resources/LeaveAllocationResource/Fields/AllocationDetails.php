<?php

namespace App\Filament\Resources\LeaveAllocationResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Models\Employee;

class AllocationDetails
{
    public static function make(): array
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
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}
