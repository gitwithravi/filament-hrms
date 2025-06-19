<?php

namespace App\Filament\Resources\LeaveRequestResource;

use Filament\Forms\Form;
use App\Filament\Resources\LeaveRequestResource\Fields\LeaveDetails;
use App\Filament\Resources\LeaveRequestResource\Fields\AdditionalDetails;

class FormSchema
{
    public static function make(Form $form): Form
    {
        return $form
            ->schema([
            ...LeaveDetails::make(),
            ...AdditionalDetails::make(),
            ]);
    }

    /**
     * Original form content from generated resource:
     * You can use this as reference or replace the schema above
     */
    public static function originalForm(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }
}