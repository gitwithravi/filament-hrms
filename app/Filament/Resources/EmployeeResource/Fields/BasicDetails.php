<?php

namespace App\Filament\Resources\EmployeeResource\Fields;

use App\Enums\Salutation;
use App\Enums\Gender;
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
                    Select::make('department_id')
                        ->relationship('department', 'name')
                        ->required(),
                    Select::make('designation_id')
                        ->relationship('designation', 'name')
                        ->required(),
                    Select::make('manager_id')
                        ->relationship('manager', 'full_name'),
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