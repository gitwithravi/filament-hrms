<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Models\Employee;
use App\Filament\Resources\LeaveRequestResource;
use App\Services\LeaveRequestService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

            Notification::make()
                ->title('Error')
                ->body('No employee record found for the logged-in user.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Add employee_id to data for validation
        $data['employee_id'] = $employee->id;

        // Validate leave request using LeaveRequestService
        try {
            $leaveRequestService = app(LeaveRequestService::class);
            $validationResult = $leaveRequestService->validateLeaveRequest($data);

            Log::info('Leave request validation passed', $validationResult);

            // Show success notification with validation details
            Notification::make()
                ->title('Validation Successful')
                ->body("Leave request validated. Required: {$validationResult['required_days']} days, Available: {$validationResult['available_days']} days.")
                ->success()
                ->send();

        } catch (ValidationException $e) {
            Log::error('Leave request validation failed', ['errors' => $e->errors()]);

            // Show error notification
            $errorMessages = collect($e->errors())->flatten()->implode(' ');
            Notification::make()
                ->title('Validation Failed')
                ->body($errorMessages)
                ->danger()
                ->send();

            $this->halt();
        }

        $data['status'] = 'requested';
        $data['requester_user_id'] = $userId;
        $data['approver_user_id'] = $approverId;

        return $data;
    }
}
