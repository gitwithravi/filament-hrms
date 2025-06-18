<?php

namespace App\Filament\Resources\DesignationResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\Designation;

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
                    Select::make('parent_id')
                        ->label('Parent Designation')
                        ->options(Designation::all()->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
        ];
    }
}