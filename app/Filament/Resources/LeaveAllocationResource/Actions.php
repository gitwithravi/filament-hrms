<?php

namespace App\Filament\Resources\LeaveAllocationResource;

use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;

class Actions
{
    public static function getActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('viewRecords')
                    ->label('View Records')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => "Leave Records for {$record->employee->name}")
                    ->modalWidth(MaxWidth::FourExtraLarge)
                    ->infolist(fn (Infolist $infolist): Infolist => $infolist
                        ->schema([


                            Section::make('Leave Type Allocations')
                                ->schema([
                                    RepeatableEntry::make('leaveAllocationRecords')
                                        ->label('')
                                        ->schema([
                                            TextEntry::make('leaveType.name')
                                                ->label('Leave Type')
                                                ->weight('bold'),
                                            TextEntry::make('allotted')
                                                ->label('Allotted')
                                                ->suffix(' days')
                                                ->color('primary'),
                                            TextEntry::make('used')
                                                ->label('Used')
                                                ->suffix(' days')
                                                ->color('warning'),
                                            TextEntry::make('remaining')
                                                ->label('Remaining')
                                                ->getStateUsing(fn ($record) => $record->allotted - $record->used)
                                                ->suffix(' days')
                                                ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                                        ])
                                        ->columns(4)
                                        ->contained(false)
                                        ->grid(1)
                                        ->extraAttributes(['class' => 'border rounded-lg p-4 mb-2']),
                                ])
                                ->collapsible()
                                ->persistCollapsed(),
                        ])
                    ),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

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