<?php

namespace App\Filament\Resources\EmployeeCategoryResource;

use Filament\Tables\Table;
use Filament\Tables;
use App\Filament\Resources\EmployeeCategoryResource\Actions;

class TableSchema
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
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
                //
            ])
            ->actions(Actions::getActions())
            ->bulkActions(Actions::getBulkActions());
    }
}