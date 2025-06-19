<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('monthly-view')
                ->label('View Monthly Attendance')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->url(fn (): string => static::getResource()::getUrl('monthly-view')),
        ];
    }
}
