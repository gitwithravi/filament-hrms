<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Filament\Support\Enums\FontWeight;
use Filament\Actions;
use App\Models\LeaveRequest;
use App\Enums\LeaveRequestStatus;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class ViewLeaveRequest extends ViewRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Leave Action')
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
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => $data['status'],
                        'approver_comment' => $data['approver_comment'],
                    ]);

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->visible(function () {
                    if(Auth::user()->hasRole('super_admin')) {
                        return $this->record->status === LeaveRequestStatus::REQUESTED;
                    }
                    return $this->record->status === LeaveRequestStatus::REQUESTED && $this->record->approver_user_id === auth()->user()->id;
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Leave Request Details')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Employee Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('employee.full_name')
                                                    ->label('Employee Name')
                                                    ->weight(FontWeight::Bold)
                                                    ->icon('heroicon-o-user'),
                                                TextEntry::make('employee.employeeRecords')
                                                    ->label('Department')
                                                    ->icon('heroicon-o-building-office')
                                                    ->formatStateUsing(function ($record) {
                                                        $activeRecord = $record->employee->employeeRecords()
                                                            ->active()
                                                            ->with('department')
                                                            ->first();
                                                        return $activeRecord?->department?->name ?? 'Not assigned';
                                                    }),
                                                TextEntry::make('employee.employeeRecords')
                                                    ->label('Designation')
                                                    ->icon('heroicon-o-briefcase')
                                                    ->formatStateUsing(function ($record) {
                                                        $activeRecord = $record->employee->employeeRecords()
                                                            ->active()
                                                            ->with('designation')
                                                            ->first();
                                                        return $activeRecord?->designation?->name ?? 'Not assigned';
                                                    }),
                                                TextEntry::make('employee.emp_id')
                                                    ->label('Employee ID')
                                                    ->icon('heroicon-o-identification'),
                                            ])
                                    ])
                                    ->collapsible(),

                                Section::make('Leave Details')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('leaveType.name')
                                                    ->label('Leave Type')
                                                    ->badge()
                                                    ->color('primary')
                                                    ->icon('heroicon-o-calendar-days'),
                                                TextEntry::make('status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state->value) {
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        'requested' => 'warning',
                                                        'withdrawn' => 'gray',
                                                        default => 'gray',
                                                    })
                                                    ->icon(fn ($state) => match ($state->value) {
                                                        'approved' => 'heroicon-o-check-circle',
                                                        'rejected' => 'heroicon-o-x-circle',
                                                        'requested' => 'heroicon-o-clock',
                                                        'withdrawn' => 'heroicon-o-arrow-uturn-left',
                                                        default => 'heroicon-o-question-mark-circle',
                                                    }),
                                                TextEntry::make('start_date')
                                                    ->label('Start Date')
                                                    ->date('F j, Y')
                                                    ->icon('heroicon-o-calendar'),
                                                TextEntry::make('end_date')
                                                    ->label('End Date')
                                                    ->date('F j, Y')
                                                    ->icon('heroicon-o-calendar'),
                                                TextEntry::make('is_half_date')
                                                    ->label('Half Day Leave')
                                                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                                    ->badge()
                                                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                                                    ->icon(fn ($state) => $state ? 'heroicon-o-clock' : 'heroicon-o-calendar-days'),
                                                TextEntry::make('half_day_shift')
                                                    ->label('Half Day Shift')
                                                    ->visible(fn ($record) => $record->is_half_date)
                                                    ->badge()
                                                    ->color('info')
                                                    ->icon('heroicon-o-sun'),
                                            ])
                                    ]),
                            ]),

                        Tabs\Tab::make('Request Details')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Request Information')
                                    ->schema([
                                        TextEntry::make('reason')
                                            ->label('Reason for Leave')
                                            ->columnSpanFull()
                                            ->prose()
                                            ->markdown()
                                            ->icon('heroicon-o-chat-bubble-left-ellipsis'),
                                        TextEntry::make('alternate_arrangement')
                                            ->label('Alternate Arrangement')
                                            ->columnSpanFull()
                                            ->prose()
                                            ->markdown()
                                            ->icon('heroicon-o-users'),
                                    ]),

                                Section::make('Supporting Documents')
                                    ->schema([
                                        ImageEntry::make('leave_file')
                                            ->label('Attached File')
                                            ->disk('public')
                                            ->visibility('private')
                                            ->columnSpanFull()
                                            ->visible(fn ($record) => !empty($record->leave_file)),
                                        TextEntry::make('leave_file')
                                            ->label('No file attached')
                                            ->formatStateUsing(fn () => 'No supporting documents provided')
                                            ->color('gray')
                                            ->icon('heroicon-o-document')
                                            ->visible(fn ($record) => empty($record->leave_file)),
                                    ])
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('Approval Details')
                            ->icon('heroicon-o-check-badge')
                            ->schema([
                                Section::make('Requester Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('requesterUser.name')
                                                    ->label('Requested By')
                                                    ->weight(FontWeight::Bold)
                                                    ->icon('heroicon-o-user'),
                                                TextEntry::make('created_at')
                                                    ->label('Request Date')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->icon('heroicon-o-clock'),
                                            ])
                                    ]),

                                Section::make('Approver Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('approverUser.name')
                                                    ->label('Approver')
                                                    ->weight(FontWeight::Bold)
                                                    ->icon('heroicon-o-user')
                                                    ->placeholder('Not assigned yet'),
                                                TextEntry::make('updated_at')
                                                    ->label('Last Updated')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->icon('heroicon-o-clock')
                                                    ->visible(fn ($record) => $record->status->value !== 'requested'),
                                            ])
                                    ]),

                                Section::make('Approval Comments')
                                    ->schema([
                                        TextEntry::make('approver_comment')
                                            ->label('Approver Comments')
                                            ->columnSpanFull()
                                            ->prose()
                                            ->markdown()
                                            ->icon('heroicon-o-chat-bubble-left-right')
                                            ->placeholder('No comments provided')
                                            ->visible(fn ($record) => !empty($record->approver_comment)),
                                        TextEntry::make('no_comments')
                                            ->label('Approver Comments')
                                            ->formatStateUsing(fn () => 'No comments provided yet')
                                            ->color('gray')
                                            ->icon('heroicon-o-chat-bubble-left-right')
                                            ->visible(fn ($record) => empty($record->approver_comment)),
                                    ])
                                    ->collapsed(fn ($record) => empty($record->approver_comment)),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
