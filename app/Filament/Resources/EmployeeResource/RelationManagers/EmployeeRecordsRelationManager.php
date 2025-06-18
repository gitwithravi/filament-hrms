<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Department;
use App\Models\Designation;

class EmployeeRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'employeeRecords';

    protected static ?string $recordTitleAttribute = 'department.name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('department_id')
                    ->label('Department')
                    ->options(Department::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                Forms\Components\Select::make('designation_id')
                    ->label('Designation')
                    ->options(Designation::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now()),

                Forms\Components\DatePicker::make('end_date')
                    ->label('End Date')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->nullable()
                    ->after('start_date'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('department.name')
            ->columns([
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('designation.name')
                    ->label('Designation')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('d M, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('d M, Y')
                    ->sortable()
                    ->placeholder('Current'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->getStateUsing(fn ($record) => is_null($record->end_date))
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(Department::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('designation_id')
                    ->label('Designation')
                    ->options(Designation::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('active_only')
                    ->label('Active Records Only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('end_date'))
                    ->default(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Record'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('end_record')
                    ->label('End Record')
                    ->icon('heroicon-s-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => is_null($record->end_date))
                    ->requiresConfirmation()
                    ->modalHeading('End Employee Record')
                    ->modalDescription('Are you sure you want to end this employee record? This will set the end date to today.')
                    ->action(function ($record) {
                        $record->update(['end_date' => now()->toDateString()]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }
}
