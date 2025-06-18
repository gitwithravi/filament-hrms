<?php

namespace App\Filament\Resources\LeaveAllocationResource;

use Filament\Tables;
use App\Enums\UserType;
use App\Models\Employee;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\LeaveAllocationResource\Actions;

class TableSchema
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->description(fn ($record) => 'Total Allocations: ' . $record->employee->leaveAllocations()->count()),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable()
                    ->hidden(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->hidden(),

                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->hidden(),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->options(Employee::pluck('full_name', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => auth()->user()->user_type != UserType::EMPLOYEE),
            ])
            ->actions(Actions::getActions())
            ->bulkActions(Actions::getBulkActions());
    }
}
