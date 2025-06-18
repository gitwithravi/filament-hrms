<?php

namespace App\Filament\Resources\TimesheetResource;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Database\Eloquent\Builder;

class TableSchema
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('in_at')
                    ->label('Clock In')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('out_at')
                    ->label('Clock Out')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('workShift.name')
                    ->label('Work Shift')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(function ($record) {
                        if ($record->in_at && $record->out_at) {
                            $duration = $record->out_at->diff($record->in_at);
                            return $duration->format('%H:%I:%S');
                        }
                        return '-';
                    })
                    ->sortable(false),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee_id')
                    ->relationship('employee', 'full_name')
                    ->label('Employee'),
                Tables\Filters\SelectFilter::make('work_shift_id')
                    ->relationship('workShift', 'name')
                    ->label('Work Shift'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('clock_in_time')
                    ->form([
                        TimePicker::make('in_from'),
                        TimePicker::make('in_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['in_from'],
                                fn (Builder $query, $time): Builder => $query->whereTime('in_at', '>=', $time),
                            )
                            ->when(
                                $data['in_until'],
                                fn (Builder $query, $time): Builder => $query->whereTime('in_at', '<=', $time),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
