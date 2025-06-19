<?php

namespace App\Filament\Resources\LeaveRequestResource\Fields;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;

class AdditionalDetails
{
    public static function make(): array
    {
        return [
            Section::make('AdditionalDetails')
                ->schema([
                    Textarea::make('reason')
                        ->required(),
                    Textarea::make('alternate_arrangement')
                        ->required(),
                    FileUpload::make('leave_file')
                    ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}