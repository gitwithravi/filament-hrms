<?php

namespace App\Filament\Resources\EmployeeResource\Fields;

use App\Enums\Gender;
use App\Enums\Salutation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;

class BasicDetails
{
    public static function make(): array
    {
        return [
            Section::make('BasicDetails')
                ->schema([
                    Select::make('salutation')
                        ->options(Salutation::getOptions())
                        ->required(),
                    TextInput::make('full_name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('emp_id')
                        ->required()
                        ->maxLength(255),
                    Select::make('employee_category_id')
                        ->relationship('employeeCategory', 'name')
                        ->required(),
                    DatePicker::make('date_of_joining')
                        ->required(),
                    DatePicker::make('dob')
                        ->label('Date of Birth')
                        ->required(),
                    Select::make('gender')
                        ->options(Gender::getOptions())
                        ->required(),
                ])
                ->columns(2),
        ];
    }
}