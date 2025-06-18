<?php

namespace App\Filament\Resources\LeaveAllocationResource;

use Filament\Forms\Form;
use App\Filament\Resources\LeaveAllocationResource\Fields\AllocationDetails;
use App\Filament\Resources\LeaveAllocationResource\Fields\Period;

class FormSchema
{
    public static function make(Form $form): Form
    {
        $record = $form->getRecord();

        return $form
            ->schema([
            ...AllocationDetails::make($record),
            ...Period::make($record),
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
