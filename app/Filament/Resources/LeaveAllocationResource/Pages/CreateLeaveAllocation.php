<?php

namespace App\Filament\Resources\LeaveAllocationResource\Pages;

use App\Filament\Resources\LeaveAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\LeaveAllocationResource\Rules;
use Illuminate\Validation\ValidationException;

class CreateLeaveAllocation extends CreateRecord
{
    protected static string $resource = LeaveAllocationResource::class;
    protected static bool $canCreateAnother = false;



    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Additional validation before creating
        if (isset($data['employee_id']) && isset($data['start_date']) && isset($data['end_date'])) {
            if (Rules::hasOverlappingAllocation($data['employee_id'], $data['start_date'], $data['end_date'])) {
                throw ValidationException::withMessages([
                    'start_date' => 'This employee already has a leave allocation that overlaps with the selected period.',
                    'end_date' => 'This employee already has a leave allocation that overlaps with the selected period.',
                ]);
            }
        }

        return $data;
    }
}
