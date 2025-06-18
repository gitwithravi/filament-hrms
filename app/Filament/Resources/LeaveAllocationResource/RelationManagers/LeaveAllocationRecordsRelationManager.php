<?php

namespace App\Filament\Resources\LeaveAllocationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\LeaveType;

class LeaveAllocationRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'leaveAllocationRecords';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('leave_type_id')
                    ->label('Leave Type')
                    ->options(LeaveType::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('allotted')
                    ->label('Allotted')
                    ->numeric()
                    ->step(0.5)
                    ->minValue(0)
                    ->required(),

                Forms\Components\TextInput::make('used')
                    ->label('Used')
                    ->numeric()
                    ->step(0.5)
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('leave_type.name')
            ->columns([
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('allotted')
                    ->label('Allotted')
                    ->numeric()
                    ->sortable()
                    ->suffix(' days'),

                Tables\Columns\TextColumn::make('used')
                    ->label('Used')
                    ->numeric()
                    ->sortable()
                    ->suffix(' days'),

                Tables\Columns\TextColumn::make('remaining')
                    ->label('Remaining')
                    ->getStateUsing(fn ($record) => $record->allotted - $record->used)
                    ->numeric()
                    ->suffix(' days')
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'warning')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
