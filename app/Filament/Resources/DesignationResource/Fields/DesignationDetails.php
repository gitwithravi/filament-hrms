<?php

namespace App\Filament\Resources\DesignationResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class DesignationDetails
{
    public static function make(): array
    {
        return [
            Section::make('DesignationDetails')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2),
        ];
    }
}