<?php

namespace App\Filament\Resources\AttendanceTypeResource\Pages;

use App\Filament\Resources\AttendanceTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceType extends EditRecord
{
    protected static string $resource = AttendanceTypeResource::class;

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
