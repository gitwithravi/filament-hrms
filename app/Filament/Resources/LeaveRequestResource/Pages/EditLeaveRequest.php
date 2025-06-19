<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use App\Services\LeaveRequestService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    //customize redirect after edit
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate leave request using LeaveRequestService (excluding current record)
        try {
            $leaveRequestService = app(LeaveRequestService::class);
            $validationResult = $leaveRequestService->validateLeaveRequest($data, $this->record->id);

            Log::info('Leave request validation passed during edit', $validationResult);

            // Show success notification with validation details
            Notification::make()
                ->title('Validation Successful')
                ->body("Leave request updated successfully. Required: {$validationResult['required_days']} days, Available: {$validationResult['available_days']} days.")
                ->success()
                ->send();

        } catch (ValidationException $e) {
            Log::error('Leave request validation failed during edit', ['errors' => $e->errors()]);

            // Show error notification
            $errorMessages = collect($e->errors())->flatten()->implode(' ');
            Notification::make()
                ->title('Validation Failed')
                ->body($errorMessages)
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
