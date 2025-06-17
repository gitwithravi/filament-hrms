<?php

namespace App\Filament\Resources\DepartmentResource;

use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class TableSchema
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('created_at')
                ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime('d-m-Y H:i'),
                TextColumn::make('updated_at')
                ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime('d-m-Y H:i'),
            ])
            ->filters([
                //
            ])
            ->actions(Actions::getActions())
            ->bulkActions(Actions::getBulkActions());
    }
}
