<?php

namespace App\Filament\Resources\LeaveTypeResource;

use Filament\Forms\Form;
use App\Filament\Resources\LeaveTypeResource\Fields\Details;

class FormSchema
{
    public static function make(Form $form): Form
    {
        return $form
            ->schema([
            ...Details::make(),
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