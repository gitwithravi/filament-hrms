<?php

namespace App\Traits;

use App\Enums\UserType;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeRecord;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

trait HasManager
{
    /**
     * Boot the trait.
     */
    public static function bootHasManager(): void
    {
        // Check if the model has employee_id column
        static::creating(function ($model) {
            if (!Schema::hasColumn($model->getTable(), 'employee_id')) {
                throw new \Exception("Model {$model->getTable()} must have an 'employee_id' column to use HasManager trait.");
            }
        });
    }

        /**
     * Scope to filter records for the current manager based on designation hierarchy.
     * Only applies if the current user's user_type is 'manager'.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeForManager(Builder $query): Builder
    {
        // Only apply the scope if the current user is a manager
        if (!Auth::check() || Auth::user()->user_type !== UserType::MANAGER) {
            return $query;
        }

        $managedEmployeeIds = $this->getCurrentUserManagedEmployeeIds();

        if ($managedEmployeeIds->isEmpty()) {
            // If no managed employees, return empty result
            return $query->where('employee_id', -1);
        }

        return $query->whereIn('employee_id', $managedEmployeeIds);
    }

        /**
     * Get the employee IDs that the current user manages based on designation hierarchy.
     * Only works if the current user's user_type is 'manager'.
     *
     * @return Collection
     */
    protected function getCurrentUserManagedEmployeeIds(): Collection
    {
        try {
            // Only proceed if the current user is a manager
            if (!Auth::check() || Auth::user()->user_type !== UserType::MANAGER) {
                return collect();
            }

            $currentUserDesignation = $this->getCurrentUserDesignation();

            if (!$currentUserDesignation) {
                return collect();
            }

            // Get all child designations (direct children and descendants)
            $childDesignationIds = $this->getChildDesignationIds($currentUserDesignation);

            if ($childDesignationIds->isEmpty()) {
                return collect();
            }

            // Find all employees with active records having these designations
            $managedEmployeeIds = EmployeeRecord::active()
                ->whereIn('designation_id', $childDesignationIds)
                ->whereHas('employee', function ($query) {
                    $query->active(); // Only active employees
                })
                ->pluck('employee_id')
                ->unique();

            return $managedEmployeeIds;
        } catch (\Exception $e) {
            // Log the error and return empty collection
            \Log::error('Error in getCurrentUserManagedEmployeeIds: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get the current user's designation from their active employee record.
     *
     * @return Designation|null
     */
    protected function getCurrentUserDesignation(): ?Designation
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        // Get the user's employee record
        $employee = $user->employee;

        if (!$employee) {
            return null;
        }

        // Get the active employee record
        $activeEmployeeRecord = $employee->employeeRecords()
            ->active()
            ->with('designation')
            ->first();

        if (!$activeEmployeeRecord || !$activeEmployeeRecord->designation) {
            return null;
        }

        return $activeEmployeeRecord->designation;
    }

    /**
     * Get all child designation IDs (direct children and descendants) for a given designation.
     *
     * @param Designation $designation
     * @return Collection
     */
    protected function getChildDesignationIds(Designation $designation): Collection
    {
        // Get direct children
        $directChildren = $designation->children()->pluck('id');

        // Get all descendants recursively
        $descendants = $this->getDescendantDesignationIds($designation);

        // Combine direct children and descendants
        return $directChildren->merge($descendants)->unique();
    }

    /**
     * Recursively get all descendant designation IDs.
     *
     * @param Designation $designation
     * @return Collection
     */
    protected function getDescendantDesignationIds(Designation $designation): Collection
    {
        $descendantIds = collect();

        $children = $designation->children;

        foreach ($children as $child) {
            $descendantIds->push($child->id);
            $descendantIds = $descendantIds->merge($this->getDescendantDesignationIds($child));
        }

        return $descendantIds->unique();
    }

    /**
     * Check if the current user can manage a specific employee.
     * Only returns true if the current user's user_type is 'manager'.
     *
     * @param int $employeeId
     * @return bool
     */
    public function canManageEmployee(int $employeeId): bool
    {
        // Only managers can manage employees
        if (!Auth::check() || Auth::user()->user_type !== UserType::MANAGER) {
            return false;
        }

        $managedEmployeeIds = $this->getCurrentUserManagedEmployeeIds();
        return $managedEmployeeIds->contains($employeeId);
    }

        /**
     * Get all employees that the current user manages.
     * Only works if the current user's user_type is 'manager'.
     *
     * @return Collection
     */
    public function getManagedEmployees(): Collection
    {
        // Only managers can have managed employees
        if (!Auth::check() || Auth::user()->user_type !== UserType::MANAGER) {
            return collect();
        }

        $managedEmployeeIds = $this->getCurrentUserManagedEmployeeIds();

        if ($managedEmployeeIds->isEmpty()) {
            return collect();
        }

        return Employee::active()
            ->whereIn('id', $managedEmployeeIds)
            ->with(['employeeRecords' => function ($query) {
                $query->active()->with('designation', 'department');
            }, 'user'])
            ->get();
    }

    /**
     * Get the count of employees that the current user manages.
     * Only works if the current user's user_type is 'manager'.
     *
     * @return int
     */
    public function getManagedEmployeesCount(): int
    {
        // Only managers can have managed employees
        if (!Auth::check() || Auth::user()->user_type !== UserType::MANAGER) {
            return 0;
        }

        return $this->getCurrentUserManagedEmployeeIds()->count();
    }
}
