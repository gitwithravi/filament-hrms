<?php

namespace App\Filament\Resources\DesignationResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class DefaultFields
{
    public static function make(): array
    {
        return [
            Section::make('DefaultFields')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required(),
                ])
                ->columns(1),
        ];
    }
}