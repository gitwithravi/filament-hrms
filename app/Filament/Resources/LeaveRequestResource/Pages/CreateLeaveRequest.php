<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Models\Employee;
use App\Filament\Resources\LeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = auth()->user()->id;
        Log::info('User ID: ' . $userId);
        $employee = Employee::where('user_id', $userId)->first();
        if ($employee) {
            Log::info('Employee ID for logged in user: ' . $employee->id);
            $manager = $employee->getManager($employee->id);
            if ($manager) {
                Log::info('Manager found for logged in user: ' . $employee->full_name);
                $approverId = $manager->user_id;
            } else {
                $approverId = null;
            }
        } else {
            Log::info('No employee found for logged in user');
            $approverId = null;
        }
        $data['employee_id'] = $employee->id;
        $data['status'] = 'requested';
        $data['requester_user_id'] = $userId;
        $data['approver_user_id'] = $approverId;
        return $data;
    }
}
