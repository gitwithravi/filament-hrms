<?php

namespace App\Traits;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasOwnRecord
{
    /**
     * Boot the trait
     */
    protected static function bootHasOwnRecord(): void
    {
        static::addGlobalScope('own_record', function (Builder $builder) {
            static::applyOwnRecordScope($builder);
        });
    }

    /**
     * Apply the own record scope
     */
    protected static function applyOwnRecordScope(Builder $builder): void
    {
        $user = Auth::user();

        // Only apply the scope if user is logged in
        if (!$user) {
            return;
        }

        // Only apply the scope if user is an employee
        if ($user->user_type !== UserType::EMPLOYEE) {
            return;
        }

        // Get the employee record for this user
        $employee = $user->employee;

        // If no employee record exists, filter out all records
        if (!$employee) {
            $builder->whereRaw('1 = 0'); // This will return no results
            return;
        }

        // Check if the model has employee_id column
        $model = $builder->getModel();
        $table = $model->getTable();

        if (!$model->getConnection()->getSchemaBuilder()->hasColumn($table, 'employee_id')) {
            throw new \Exception("Model " . get_class($model) . " must have an 'employee_id' column to use HasOwnRecord trait.");
        }

        // Filter records to only show those belonging to the current employee
        $builder->where('employee_id', $employee->id);
    }

    /**
     * Scope to get all records regardless of ownership (admin/manager access)
     */
    public function scopeWithoutOwnRecordScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('own_record');
    }

    /**
     * Scope to explicitly apply own record filtering
     */
    public function scopeOnlyOwnRecords(Builder $builder): Builder
    {
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
            return false;
        }

        // Only employees are restricted to their own records
        return $user->user_type !== UserType::EMPLOYEE;
    }
}
