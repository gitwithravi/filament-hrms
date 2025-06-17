<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceTypeResource\Pages;
use App\Filament\Resources\AttendanceTypeResource\RelationManagers;
use App\Models\AttendanceType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AttendanceTypeResource\FormSchema;
use App\Filament\Resources\AttendanceTypeResource\TableSchema;
use App\Filament\Resources\AttendanceTypeResource\Actions;

class AttendanceTypeResource extends Resource
{
    protected static ?string $model = AttendanceType::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = 'System Configuration';

    public static function form(Form $form): Form
    {
        return FormSchema::make($form);
    }

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
            'index' => Pages\ListAttendanceTypes::route('/'),
            'create' => Pages\CreateAttendanceType::route('/create'),
            'edit' => Pages\EditAttendanceType::route('/{record}/edit'),
        ];
    }
}
