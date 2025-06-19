<?php

namespace App\Observers;

use App\Models\LeaveRequest;
use App\Services\LeaveCountUpdateService;
use App\Enums\LeaveRequestStatus;

class LeaveRequestObserver
{
    public function __construct(
        private LeaveCountUpdateService $leaveCountUpdateService
    ) {}

    /**
     * Handle the LeaveRequest "created" event.
     */
    public function created(LeaveRequest $leaveRequest): void
    {
        // Handle status change when created (from null to current status)
        $this->leaveCountUpdateService->handleLeaveRequestStatusChange(
            $leaveRequest,
            null
        );
    }

    /**
     * Handle the LeaveRequest "updated" event.
     */
    public function updated(LeaveRequest $leaveRequest): void
    {
        $originalAttributes = $leaveRequest->getOriginal();

                // Check if status changed
        if ($leaveRequest->wasChanged('status')) {
            $oldStatus = null;
            if (isset($originalAttributes['status'])) {
                $oldStatus = $originalAttributes['status'] instanceof LeaveRequestStatus
                    ? $originalAttributes['status']
                    : LeaveRequestStatus::from($originalAttributes['status']);
            }

            $this->leaveCountUpdateService->handleLeaveRequestStatusChange(
                $leaveRequest,
                $oldStatus
            );
        }

                // Check if dates or half-day status changed for approved requests
        if ($leaveRequest->status === LeaveRequestStatus::APPROVED &&
            ($leaveRequest->wasChanged('start_date') ||
             $leaveRequest->wasChanged('end_date') ||
             $leaveRequest->wasChanged('is_half_date'))) {

            $this->leaveCountUpdateService->handleLeaveRequestDatesUpdate(
                $leaveRequest,
                \Carbon\Carbon::parse($originalAttributes['start_date']),
                \Carbon\Carbon::parse($originalAttributes['end_date']),
                $originalAttributes['is_half_date'] ?? false
            );
        }
    }

    /**
     * Handle the LeaveRequest "deleted" event.
     */
    public function deleted(LeaveRequest $leaveRequest): void
    {
        $this->leaveCountUpdateService->handleLeaveRequestDeletion($leaveRequest);
    }
}
