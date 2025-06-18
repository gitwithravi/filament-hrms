<?php

namespace App\Filament\Resources\LeaveAllocationResource\Pages;

use App\Filament\Resources\LeaveAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
}
