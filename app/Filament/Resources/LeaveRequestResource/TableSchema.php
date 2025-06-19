<?php

namespace App\Filament\Resources\LeaveRequestResource;

use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\LeaveRequestStatus;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\LeaveRequestResource\Actions;

class TableSchema
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        LeaveRequestStatus::REQUESTED => 'warning',
                        LeaveRequestStatus::APPROVED => 'success',
                        LeaveRequestStatus::REJECTED => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions(Actions::getActions())
            ->bulkActions(Actions::getBulkActions());
    }
}