<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Models\WorkShift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\AttachAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkShiftsRelationManager extends RelationManager
{
    protected static string $relationship = 'workShifts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('recordId')
                    ->label('Work Shift')
                    ->options(WorkShift::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->default(now()),

                Forms\Components\DatePicker::make('end_date')
                    ->label('End Date')
                    ->after('start_date')
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Work Shift Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start Time')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('End Time')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.start_date')
                    ->label('Assignment Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.end_date')
                    ->label('Assignment End Date')
                    ->date()
                    ->sortable()
                    ->placeholder('Active'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => is_null($record->pivot->end_date) || \Carbon\Carbon::parse($record->pivot->end_date)->isFuture())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Active Assignments')
                    ->query(fn (Builder $query): Builder => $query->whereNull('employee_work_shift.end_date')),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->searchable()
                            ->preload(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->after('start_date')
                            ->nullable(),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->after('start_date')
                            ->nullable(),
                    ]),

                Tables\Actions\DetachAction::make()
                    ->label('End Assignment')
                    ->modalHeading('End Work Shift Assignment')
                    ->modalDescription('This will set the end date for this work shift assignment.')
                    ->form([
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->default(now())
                            ->after('start_date'),
                    ])
                    ->action(function (array $data, $record) {
                        $this->getOwnerRecord()
                            ->workShifts()
                            ->updateExistingPivot($record->id, [
                                'end_date' => $data['end_date']
                            ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
