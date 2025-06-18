<?php

namespace App\Filament\Resources\HolidayResource;

use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;

class Actions
{
    public static function getActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->label('Actions')
            ->icon('heroicon-o-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    public static function getBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }

    public static function getHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make(),
        ];
    }
}
