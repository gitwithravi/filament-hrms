<?php

namespace App\Filament\Resources\LeaveAllocationResource\Pages;

use App\Filament\Resources\LeaveAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveAllocations extends ListRecords
{
    protected static string $resource = LeaveAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
