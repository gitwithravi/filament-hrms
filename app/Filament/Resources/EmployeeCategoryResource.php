<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeCategoryResource\Pages;
use App\Filament\Resources\EmployeeCategoryResource\RelationManagers;
use App\Models\EmployeeCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmployeeCategoryResource\FormSchema;
use App\Filament\Resources\EmployeeCategoryResource\TableSchema;
use App\Filament\Resources\EmployeeCategoryResource\Actions;

class EmployeeCategoryResource extends Resource
{
    protected static ?string $model = EmployeeCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
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
            'index' => Pages\ListEmployeeCategories::route('/'),
            'create' => Pages\CreateEmployeeCategory::route('/create'),
            'edit' => Pages\EditEmployeeCategory::route('/{record}/edit'),
        ];
    }
}
