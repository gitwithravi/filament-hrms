<?php

namespace App\Filament\Resources\HolidayResource;

use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\HolidayType;

class TableSchema
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('from_date')
                    ->date()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to_date')
                    ->date()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('holiday_type')
                    ->badge()
                    ->color(fn (HolidayType $state): string => match ($state) {
                        HolidayType::GLOBAL => 'success',
                        HolidayType::SECTIONAL => 'warning',
                    })
                    ->searchable()
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('holiday_type')
                    ->options(HolidayType::options()),
            ])
            ->actions(Actions::getActions())
            ->bulkActions(Actions::getBulkActions());
    }
}
