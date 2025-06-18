<?php

namespace App\Filament\Resources\LeaveAllocationResource\Pages;

use App\Filament\Resources\LeaveAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveAllocation extends CreateRecord
{
    protected static string $resource = LeaveAllocationResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
