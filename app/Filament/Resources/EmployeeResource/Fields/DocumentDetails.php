<?php

namespace App\Filament\Resources\EmployeeResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class DocumentDetails
{
    public static function make(): array
    {
        return [
            Section::make('DocumentDetails')
                ->schema([
                    TextInput::make('aadhar_no')
                        ->maxLength(255),
                    TextInput::make('pan_no')
                        ->maxLength(255),
                    FileUpload::make('photograph')
                        ->label('Photograph')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '16:9',
                            '4:3',
                        ])
                        ->columnSpanFull(),

                ])
                ->columns(2),
        ];
    }
}