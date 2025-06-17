<?php

namespace App\Filament\Resources\EmployeeCategoryResource\Pages;

use App\Filament\Resources\EmployeeCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeCategory extends CreateRecord
{
    protected static string $resource = EmployeeCategoryResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
