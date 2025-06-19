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
use Illuminate\Support\Facades\Log;

trait HasManager
{
    /**
     * Boot the trait.
     */
        public static function bootHasManager(): void
    {
        Log::info('HasManager::bootHasManager - Booting trait');

        // Check if the model has employee_id column (skip for employees table)
        static::creating(function ($model) {
            if ($model->getTable() !== 'employees' && !Schema::hasColumn($model->getTable(), 'employee_id')) {
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
            Log::info('HasManager::forManager - User not authenticated or not a manager');
            return $query;
        }

        $managedEmployeeIds = $this->getCurrentUserManagedEmployeeIds();

        if ($managedEmployeeIds->isEmpty()) {
            Log::info('HasManager::forManager - No managed employees');
            // If no managed employees, return empty result
            $column = $this->getTable() === 'employees' ? 'id' : 'employee_id';
            return $query->where($column, -1);
        }

        Log::info('HasManager::forManager - Managed employees found', ['employee_ids' => $managedEmployeeIds]);

        // Use appropriate column based on table
        $column = $this->getTable() === 'employees' ? 'id' : 'employee_id';
        return $query->whereIn($column, $managedEmployeeIds);
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
            Log::debug('HasManager::getCurrentUserDesignation - User not authenticated');
            return null;
        }

        $user = Auth::user();
        Log::debug('HasManager::getCurrentUserDesignation - Got user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_type' => $user->user_type?->value,
        ]);

        // Get the user's employee record - bypass global scopes to avoid conflicts
        try {
            $employee = Employee::withoutGlobalScopes()->where('user_id', $user->id)->first();
            Log::debug('HasManager::getCurrentUserDesignation - Employee found via direct query', [
                'employee' => $employee ? $employee->id : null,
            ]);
        } catch (\Exception $e) {
            Log::error('HasManager::getCurrentUserDesignation - Error getting employee', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            return null;
        }

        if (!$employee) {
            Log::warning('HasManager::getCurrentUserDesignation - No employee record found for user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
            return null;
        }

        Log::debug('HasManager::getCurrentUserDesignation - Found employee', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
        ]);

        // Get the active employee record
        $activeEmployeeRecord = $employee->employeeRecords()
            ->active()
            ->with('designation')
            ->first();

        if (!$activeEmployeeRecord) {
            Log::warning('HasManager::getCurrentUserDesignation - No active employee record found', [
                'employee_id' => $employee->id,
            ]);
            return null;
        }

        if (!$activeEmployeeRecord->designation) {
            Log::warning('HasManager::getCurrentUserDesignation - Active employee record has no designation', [
                'employee_id' => $employee->id,
                'employee_record_id' => $activeEmployeeRecord->id,
            ]);
            return null;
        }

        Log::debug('HasManager::getCurrentUserDesignation - Found designation', [
            'designation_id' => $activeEmployeeRecord->designation->id,
            'designation_name' => $activeEmployeeRecord->designation->name,
        ]);

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
            Log::info('HasManager::getManagedEmployeesCount - User not authenticated or not a manager');
            return 0;
        }

        return $this->getCurrentUserManagedEmployeeIds()->count();
    }

    /**
     * Get the manager (Employee instance) for the given employee.
     * This method finds the employee who has the parent designation of the current employee's designation.
     *
     * @param int|null $employeeId The employee ID to find the manager for. If null, uses the current authenticated user's employee.
     * @return Employee|null
     */
    public function getManager(?int $employeeId = null): ?Employee
    {
        try {
            \Log::info('HasManager::getManager called', [
                'input_employee_id' => $employeeId,
                'auth_user_id' => Auth::id(),
                'auth_user_type' => Auth::check() ? Auth::user()->user_type?->value : null,
            ]);

            // If no employee ID is provided, try to get the current user's employee
            if ($employeeId === null) {
                \Log::debug('HasManager::getManager - No employee ID provided, getting from current user');

                if (!Auth::check()) {
                    \Log::warning('HasManager::getManager - User not authenticated');
                    return null;
                }

                $user = Auth::user();
                \Log::debug('HasManager::getManager - Got authenticated user', [
                    'user_id' => $user->id,
                    'user_type' => $user->user_type?->value,
                ]);

                $employee = $user->employee;

                if (!$employee) {
                    \Log::warning('HasManager::getManager - Current user has no associated employee', [
                        'user_id' => $user->id,
                    ]);
                    return null;
                }

                $employeeId = $employee->id;
                \Log::debug('HasManager::getManager - Using current user employee ID', [
                    'employee_id' => $employeeId,
                ]);
            }

            \Log::debug('HasManager::getManager - Finding employee', [
                'employee_id' => $employeeId,
            ]);

            // Get the employee
            $employee = Employee::find($employeeId);

            if (!$employee) {
                \Log::warning('HasManager::getManager - Employee not found', [
                    'employee_id' => $employeeId,
                ]);
                return null;
            }

            \Log::debug('HasManager::getManager - Found employee', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'employee_status' => $employee->status?->value,
            ]);

            // Get the employee's active employee record
            $activeEmployeeRecord = $employee->employeeRecords()
                ->active()
                ->with('designation')
                ->first();

            if (!$activeEmployeeRecord) {
                \Log::warning('HasManager::getManager - No active employee record found', [
                    'employee_id' => $employee->id,
                ]);
                return null;
            }

            if (!$activeEmployeeRecord->designation) {
                \Log::warning('HasManager::getManager - Active employee record has no designation', [
                    'employee_id' => $employee->id,
                    'employee_record_id' => $activeEmployeeRecord->id,
                ]);
                return null;
            }

            $currentDesignation = $activeEmployeeRecord->designation;
            \Log::debug('HasManager::getManager - Found employee current designation', [
                'employee_id' => $employee->id,
                'designation_id' => $currentDesignation->id,
                'designation_name' => $currentDesignation->name,
                'designation_parent_id' => $currentDesignation->parent_id,
            ]);

            // Get the parent designation
            $parentDesignation = $currentDesignation->parent;

            if (!$parentDesignation) {
                \Log::info('HasManager::getManager - No parent designation found (top-level designation)', [
                    'employee_id' => $employee->id,
                    'designation_id' => $currentDesignation->id,
                    'designation_name' => $currentDesignation->name,
                ]);
                return null;
            }

            \Log::debug('HasManager::getManager - Found parent designation', [
                'employee_id' => $employee->id,
                'parent_designation_id' => $parentDesignation->id,
                'parent_designation_name' => $parentDesignation->name,
            ]);

            // Find the employee who has this parent designation as their active designation
            $managerEmployeeRecord = EmployeeRecord::active()
                ->where('designation_id', $parentDesignation->id)
                ->with(['employee' => function ($query) {
                    $query->withoutGlobalScope('own_record')->active(); // Only active employees, bypassing own record scope
                }])
                ->first();

            if (!$managerEmployeeRecord) {
                \Log::warning('HasManager::getManager - No active employee record found for parent designation', [
                    'employee_id' => $employee->id,
                    'parent_designation_id' => $parentDesignation->id,
                    'parent_designation_name' => $parentDesignation->name,
                ]);
                return null;
            }

            if (!$managerEmployeeRecord->employee) {
                \Log::warning('HasManager::getManager - Manager employee record found but employee is inactive or missing', [
                    'employee_id' => $employee->id,
                    'manager_employee_record_id' => $managerEmployeeRecord->id,
                    'parent_designation_id' => $parentDesignation->id,
                ]);
                return null;
            }

            $manager = $managerEmployeeRecord->employee;
            \Log::info('HasManager::getManager - Successfully found manager', [
                'employee_id' => $employee->id,
                'manager_id' => $manager->id,
                'manager_name' => $manager->first_name . ' ' . $manager->last_name,
                'manager_designation_id' => $parentDesignation->id,
                'manager_designation_name' => $parentDesignation->name,
            ]);

            return $manager;

        } catch (\Exception $e) {
            \Log::error('HasManager::getManager - Exception occurred', [
                'input_employee_id' => $employeeId,
                'auth_user_id' => Auth::id(),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

}
