<?php

namespace App\Filament\Resources\LeaveRequestResource;

use Filament\Tables;
use App\Models\LeaveRequest;
use App\Enums\LeaveRequestStatus;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ActionGroup;

class Actions
{
    public static function getActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\EditAction::make()
                ->visible(function ($record) {
                    return $record->status === LeaveRequestStatus::REQUESTED;
                }),
                Tables\Actions\DeleteAction::make()
                ->visible(function ($record) {
                    return $record->status === LeaveRequestStatus::REQUESTED;
                }),
                Tables\Actions\ViewAction::make()
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
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
                    ->visible(function ($record) {
                        if(Auth::user()->hasRole('super_admin')) {
                            return $record->status === LeaveRequestStatus::REQUESTED;
                        }
                        return $record->status === LeaveRequestStatus::REQUESTED && $record->approver_id === auth()->user()->id;
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
