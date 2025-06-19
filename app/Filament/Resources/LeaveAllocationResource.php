<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveAllocationResource\Pages;
use App\Filament\Resources\LeaveAllocationResource\RelationManagers;
use App\Models\LeaveAllocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LeaveAllocationResource\FormSchema;
use App\Filament\Resources\LeaveAllocationResource\TableSchema;
use App\Filament\Resources\LeaveAllocationResource\Actions;

class LeaveAllocationResource extends Resource
{
    protected static ?string $model = LeaveAllocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Leave Allocations';
    protected static ?string $navigationGroup = 'Leave Management';
    protected static ?int $navigationSort = 1;

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
            RelationManagers\LeaveAllocationRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveAllocations::route('/'),
            'create' => Pages\CreateLeaveAllocation::route('/create'),
            'edit' => Pages\EditLeaveAllocation::route('/{record}/edit'),
        ];
    }
}
