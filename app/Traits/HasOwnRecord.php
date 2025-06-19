<?php

namespace App\Traits;

use App\Enums\UserType;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

trait HasOwnRecord
{
    /**
     * Boot the trait
     */
    protected static function bootHasOwnRecord(): void
    {
        Log::info('HasOwnRecord: Booting trait for model: ' . static::class);

        static::addGlobalScope('own_record', function (Builder $builder) {
            Log::info('HasOwnRecord: Applying global scope for model: ' . get_class($builder->getModel()));
            static::applyOwnRecordScope($builder);
        });

        Log::info('HasOwnRecord: Global scope added successfully for model: ' . static::class);
    }

    /**
     * Apply the own record scope
     */
    protected static function applyOwnRecordScope(Builder $builder): void
    {
        $modelClass = get_class($builder->getModel());
        Log::info("HasOwnRecord: Starting scope application for model: {$modelClass}");

        $user = Auth::user();

        // Only apply the scope if user is logged in
        if (!$user) {
            Log::info('HasOwnRecord: No authenticated user found - skipping scope application');
            return;
        }

        Log::info("HasOwnRecord: Authenticated user found - ID: {$user->id}, Email: {$user->email}");

        // Only apply the scope if user is an employee
        if ($user->user_type !== UserType::EMPLOYEE) {
            Log::info("HasOwnRecord: User type is {$user->user_type->value} (not EMPLOYEE) - skipping scope application");
            return;
        }

        Log::info('HasOwnRecord: User is an EMPLOYEE - proceeding with scope application');

                // Get the employee record for this user
        Log::info('HasOwnRecord: Checking for employee record...');
        $employee = Employee::withoutGlobalScope('own_record')->where('user_id', $user->id)->first();

        // If no employee record exists, filter out all records
        if (!$employee) {
            Log::warning('HasOwnRecord: No employee record found for user - filtering out all records');
            $builder->whereRaw('1 = 0'); // This will return no results
            return;
        }

        Log::info("HasOwnRecord: Employee record found - Employee ID: {$employee->id}");

                // Check if the model has employee_id column (skip for Employee model itself)
        $model = $builder->getModel();
        $table = $model->getTable();

        // If this is the Employee model, filter by the employee's own ID
        if ($model instanceof Employee) {
            Log::info("HasOwnRecord: Model is Employee - filtering by employee ID directly");
            $builder->where('id', $employee->id);
            Log::info("HasOwnRecord: Successfully applied scope - filtering Employee records where id = {$employee->id}");
            return;
        }

        Log::info("HasOwnRecord: Checking if table '{$table}' has 'employee_id' column");

        if (!$model->getConnection()->getSchemaBuilder()->hasColumn($table, 'employee_id')) {
            $errorMessage = "Model " . get_class($model) . " must have an 'employee_id' column to use HasOwnRecord trait.";
            Log::error("HasOwnRecord: {$errorMessage}");
            throw new \Exception($errorMessage);
        }

        Log::info("HasOwnRecord: Table '{$table}' has 'employee_id' column - applying filter");

        // Filter records to only show those belonging to the current employee
        $builder->where('employee_id', $employee->id);

        Log::info("HasOwnRecord: Successfully applied scope - filtering records where employee_id = {$employee->id} for model: {$modelClass}");
    }

    /**
     * Scope to get all records regardless of ownership (admin/manager access)
     */
    public function scopeWithoutOwnRecordScope(Builder $builder): Builder
    {
        $modelClass = get_class($builder->getModel());
        Log::info("HasOwnRecord: Removing 'own_record' global scope for model: {$modelClass}");

        return $builder->withoutGlobalScope('own_record');
    }

    /**
     * Scope to explicitly apply own record filtering
     */
    public function scopeOnlyOwnRecords(Builder $builder): Builder
    {
        $modelClass = get_class($builder->getModel());
        Log::info("HasOwnRecord: Explicitly applying own record scope for model: {$modelClass}");

        static::applyOwnRecordScope($builder);
        return $builder;
    }

    /**
     * Check if the current user can access all records
     */
    public static function canAccessAllRecords(): bool
    {
        $user = Auth::user();

        if (!$user) {
            Log::info('HasOwnRecord: canAccessAllRecords() - No authenticated user found');
            return false;
        }

        $canAccess = $user->user_type !== UserType::EMPLOYEE;
        Log::info("HasOwnRecord: canAccessAllRecords() - User ID: {$user->id}, Type: {$user->user_type->value}, Can access all: " . ($canAccess ? 'YES' : 'NO'));

        // Only employees are restricted to their own records
        return $canAccess;
    }
}
