<?php

namespace App\Filament\Resources\AttendanceTypeResource;

use Filament\Forms\Form;
use App\Filament\Resources\AttendanceTypeResource\Fields\AttendanceTypeDetails;

class FormSchema
{
    public static function make(Form $form): Form
    {
        return $form
            ->schema([
            ...AttendanceTypeDetails::make(),
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