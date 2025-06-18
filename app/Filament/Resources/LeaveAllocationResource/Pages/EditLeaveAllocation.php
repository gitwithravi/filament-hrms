<?php

namespace App\Filament\Resources\LeaveAllocationResource\Pages;

use App\Filament\Resources\LeaveAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\LeaveAllocationService;
use Illuminate\Validation\ValidationException;

class EditLeaveAllocation extends EditRecord
{
    protected static string $resource = LeaveAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Additional validation before saving during edit
        if (isset($data['employee_id']) && isset($data['start_date']) && isset($data['end_date'])) {
            if (LeaveAllocationService::hasOverlappingAllocation($data['employee_id'], $data['start_date'], $data['end_date'], $this->record->id)) {
                throw ValidationException::withMessages([
                    'start_date' => 'This employee already has a leave allocation that overlaps with the selected period.',
                    'end_date' => 'This employee already has a leave allocation that overlaps with the selected period.',
                ]);
            }
        }

        return $data;
    }
}
