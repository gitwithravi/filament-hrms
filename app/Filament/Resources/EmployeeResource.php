<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Filament\Resources\EmployeeResource\RelationManagers\EmployeeRecordsRelationManager;
use App\Filament\Resources\EmployeeResource\RelationManagers\WorkShiftsRelationManager;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmployeeResource\FormSchema;
use App\Filament\Resources\EmployeeResource\TableSchema;
use App\Filament\Resources\EmployeeResource\Actions;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Employee Management';

    public static function form(Form $form): Form
    {
        return FormSchema::make($form);
    }

    public static function table(Table $table): Table
    {
        return TableSchema::make($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forManager();
    }

    public static function getRelations(): array
    {
        return [
            EmployeeRecordsRelationManager::class,
            WorkShiftsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
