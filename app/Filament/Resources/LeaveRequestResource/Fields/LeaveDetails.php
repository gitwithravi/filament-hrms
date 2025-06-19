<?php

namespace App\Filament\Resources\LeaveRequestResource\Fields;

use Filament\Forms\Get;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Services\LeaveRequestService;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Carbon\Carbon;

class LeaveDetails
{
    public static function make($record = null): array
    {
        return [
            Section::make('LeaveDetails')
                ->schema([
                    Select::make('leave_type_id')
                        ->options(LeaveType::all()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->columnSpanFull(),
                    Radio::make('is_half_date')
                        ->options([
                            '1' => 'Yes',
                            '0' => 'No',
                        ])
                        ->inline()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Get $get, $set) {
                            // If half day is selected and start date is available, set end date to same as start date
                            if ($state == '1' && $get('start_date')) {
                                $set('end_date', $get('start_date'));
                            }
                        })
                        ->columnSpanFull(),
                    Radio::make('half_day_shift')
                        ->options([
                            'fn' => 'First Half',
                            'an' => 'Second Half',
                        ])
                        ->inline()
                        ->required()
                        ->visible(fn (Get $get) => $get('is_half_date') == 1)
                        ->columnSpanFull(),

                    DatePicker::make('start_date')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Get $get, $set, $livewire) {
                            // If half day is selected, automatically set end date to same as start date
                            if ($get('is_half_date') == '1' && $state) {
                                $set('end_date', $state);
                            }
                            $livewire->validateOnly('data.start_date');
                        })
                        ->rules([
                            function (Get $get) use ($record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    if (!$value) {
                                        return;
                                    }

                                    $endDate = $get('end_date');
                                    $isHalfDay = $get('is_half_date') == '1';

                                    // If half day is selected and end date is set, they must be the same
                                    if ($isHalfDay && $endDate && $value !== $endDate) {
                                        $fail('For half day leave, start date and end date must be the same.');
                                        return;
                                    }

                                    if (!$endDate) {
                                        return;
                                    }

                                    $userId = auth()->user()->id;
                                    $employee = Employee::where('user_id', $userId)->first();

                                    if (!$employee) {
                                        return;
                                    }

                                    $leaveRequestService = app(LeaveRequestService::class);
                                    $excludeRequestId = $record?->id;

                                    if ($leaveRequestService->hasOverlappingLeaveRequests($employee->id, $value, $endDate, $excludeRequestId)) {
                                        $overlappingRequests = $leaveRequestService->getOverlappingLeaveRequests($employee->id, $value, $endDate, $excludeRequestId);
                                        $overlappingDetails = $overlappingRequests->map(function ($request) {
                                            return "{$request->leaveType->name} ({$request->start_date->format('Y-m-d')} to {$request->end_date->format('Y-m-d')}) - {$request->status->label()}";
                                        })->implode(', ');

                                        $fail("Leave request overlaps with existing leave(s): {$overlappingDetails}");
                                    }
                                };
                            },
                        ]),

                    DatePicker::make('end_date')
                        ->required()
                        ->live()
                        ->afterOrEqual('start_date')
                        ->disabled(fn (Get $get) => $get('is_half_date') == '1')
                        ->dehydrated()
                        ->afterStateUpdated(function ($state, Get $get, $livewire) {
                            $livewire->validateOnly('data.end_date');
                        })
                        ->rules([
                            function (Get $get) use ($record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    if (!$value) {
                                        return;
                                    }

                                    $startDate = $get('start_date');
                                    $isHalfDay = $get('is_half_date') == '1';

                                    // If half day is selected and start date is set, they must be the same
                                    if ($isHalfDay && $startDate && $value !== $startDate) {
                                        $fail('For half day leave, start date and end date must be the same.');
                                        return;
                                    }

                                    if (!$startDate) {
                                        return;
                                    }

                                    $userId = auth()->user()->id;
                                    $employee = Employee::where('user_id', $userId)->first();

                                    if (!$employee) {
                                        return;
                                    }

                                    $leaveRequestService = app(LeaveRequestService::class);
                                    $excludeRequestId = $record?->id;

                                    if ($leaveRequestService->hasOverlappingLeaveRequests($employee->id, $startDate, $value, $excludeRequestId)) {
                                        $overlappingRequests = $leaveRequestService->getOverlappingLeaveRequests($employee->id, $startDate, $value, $excludeRequestId);
                                        $overlappingDetails = $overlappingRequests->map(function ($request) {
                                            return "{$request->leaveType->name} ({$request->start_date->format('Y-m-d')} to {$request->end_date->format('Y-m-d')}) - {$request->status->label()}";
                                        })->implode(', ');

                                        $fail("Leave request overlaps with existing leave(s): {$overlappingDetails}");
                                    }
                                };
                            },
                        ]),

                    Placeholder::make('leave_balance_info')
                        ->label('Leave Balance Information')
                        ->content(function (Get $get) use ($record): string {
                            $leaveTypeId = $get('leave_type_id');
                            $startDate = $get('start_date');
                            $endDate = $get('end_date');
                            $isHalfDay = $get('is_half_date');

                            if (!$leaveTypeId || !$startDate || !$endDate) {
                                return 'Select leave type and dates to see balance information.';
                            }

                            try {
                                $leaveRequestService = app(LeaveRequestService::class);
                                $excludeRequestId = $record?->id;

                                $validationData = [
                                    'leave_type_id' => $leaveTypeId,
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'is_half_date' => $isHalfDay == '1',
                                ];

                                $result = $leaveRequestService->validateLeaveRequestPartial($validationData, $excludeRequestId);

                                if (!$result['success']) {
                                    return "❌ " . $result['message'];
                                }

                                return "✅ Required Days: {$result['required_days']} | Available Days: {$result['available_days']} | Status: Valid";

                            } catch (\Exception $e) {
                                return "❌ Error: " . $e->getMessage();
                            }
                        })
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => $get('leave_type_id') && $get('start_date') && $get('end_date')),

                ])
                ->columns(2),
        ];
    }
}
