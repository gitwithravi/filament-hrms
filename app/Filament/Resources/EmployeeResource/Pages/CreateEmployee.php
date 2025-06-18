<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\EmployeeResource;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
            ]);
        }
        $data['user_id'] = $user->id;
        return $data;
    }
}
