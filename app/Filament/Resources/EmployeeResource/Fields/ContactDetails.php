<?php

namespace App\Filament\Resources\EmployeeResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ContactDetails
{
    public static function make(): array
    {
        return [
            Section::make('ContactDetails')
                ->schema([
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->maxLength(255),
                    Textarea::make('address')
                        ->maxLength(255),
                    TextInput::make('emergency_contact_no')
                        ->maxLength(255),
                ])
                ->columns(2),
        ];
    }
}