<?php

namespace App\Filament\Resources\EmployeeResource;

use Filament\Forms\Form;
use App\Filament\Resources\EmployeeResource\Fields\BasicDetails;
use App\Filament\Resources\EmployeeResource\Fields\ContactDetails;
use App\Filament\Resources\EmployeeResource\Fields\FamilyDetails;
use App\Filament\Resources\EmployeeResource\Fields\DocumentDetails;

class FormSchema
{
    public static function make(Form $form): Form
    {
        return $form
            ->schema([
            ...BasicDetails::make(),
            ...ContactDetails::make(),
            ...FamilyDetails::make(),
            ...DocumentDetails::make(),
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