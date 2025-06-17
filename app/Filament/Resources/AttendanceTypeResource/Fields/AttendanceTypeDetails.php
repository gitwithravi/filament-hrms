<?php

namespace App\Filament\Resources\AttendanceTypeResource\Fields;

use App\Enums\AttendanceCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class AttendanceTypeDetails
{
    public static function make(): array
    {
        return [
            Section::make('AttendanceTypeDetails')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('code')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('alias')
                        ->required()
                        ->maxLength(255),
                    Select::make('category')
                        ->options(AttendanceCategory::options())
                        ->required(),
                    Textarea::make('description')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}