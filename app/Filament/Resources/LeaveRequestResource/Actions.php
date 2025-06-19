<?php

namespace App\Filament\Resources\LeaveRequestResource;

use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use App\Enums\LeaveRequestStatus;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use App\Models\LeaveRequest;

class Actions
{
    public static function getActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('Leave Action')
                    ->label('Leave Action')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->form([
                        Textarea::make('approver_comment')
                            ->label('Approver Comment')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Status')
                            ->options(LeaveRequestStatus::class)
                            ->required(),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                            'approver_comment' => $data['approver_comment'],
                        ]);
                    })
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