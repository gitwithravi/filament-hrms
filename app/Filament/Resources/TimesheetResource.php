<?php

namespace App\Filament\Resources;

use App\Models\Timesheet;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Resources\TimesheetResource\TableSchema;

class TimesheetResource extends Resource
{
    protected static ?string $model = Timesheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Attendance Management';

    public static function table(Table $table): Table
    {
        return TableSchema::make($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\TimesheetResource\Pages\ListTimesheets::route('/'),
        ];
    }
}
