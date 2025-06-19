# HasManager Trait Documentation

## Overview

The `HasManager` trait provides hierarchical management functionality based on designation relationships. It allows filtering models to show only records belonging to employees that the current logged-in user manages through their designation hierarchy.

## Requirements

### Database Structure
- The model using this trait **must** have an `employee_id` column
- The following relationships must exist:
  - `User` → `Employee` (hasOne)
  - `Employee` → `EmployeeRecord` (hasMany)
  - `EmployeeRecord` → `Designation` (belongsTo)
  - `Designation` → `children` (hasMany for hierarchy)

### User Type Requirement
- **The trait only applies to users with `user_type` = 'manager'**
- Non-manager users will see all records without filtering
- Manager users will only see records for employees they manage

### Model Dependencies
- `App\Models\User`
- `App\Models\Employee`
- `App\Models\EmployeeRecord`
- `App\Models\Designation`
- `App\Enums\UserType`

## Installation

1. Ensure your model has an `employee_id` column
2. Add the trait to your model:

```php
<?php

namespace App\Models;

use App\Traits\HasManager;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use HasManager;
    
    protected $fillable = [
        'employee_id',
        // ... other fields
    ];
}
```

## Usage

### Basic Query Scope

The main feature is the `forManager()` query scope that filters records based on the current user's managed employees. **This scope only applies if the current user's `user_type` is 'manager'.**

```php
// Get all records for employees managed by the current user (only for managers)
$managedRecords = YourModel::forManager()->get();

// For non-manager users, this returns all records without filtering
// For manager users, this returns only records for employees they manage

// Can be combined with other scopes and conditions
$filteredRecords = YourModel::forManager()
    ->where('status', 'active')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get();
```

### Helper Methods

#### Check if Current User Can Manage an Employee

```php
$model = new YourModel();
$canManage = $model->canManageEmployee(123); // Returns boolean (false for non-managers)
```

#### Get All Managed Employees

```php
$model = new YourModel();
$managedEmployees = $model->getManagedEmployees(); // Returns Collection of Employee models (empty for non-managers)
```

#### Get Count of Managed Employees

```php
$model = new YourModel();
$count = $model->getManagedEmployeesCount(); // Returns integer (0 for non-managers)
```

#### Get Manager for an Employee

```php
$model = new YourModel();

// Get manager for the current authenticated user's employee
$manager = $model->getManager(); // Returns Employee instance or null

// Get manager for a specific employee
$manager = $model->getManager(123); // Returns Employee instance or null

// Example usage
if ($manager) {
    echo "Manager: " . $manager->full_name;
    echo "Manager Email: " . $manager->email;
} else {
    echo "No manager found or this is a top-level employee";
}
```

## How It Works

### Step-by-Step Process

1. **Check User Type**: Verifies if the current user's `user_type` is 'manager'
2. **Get Current User**: Retrieves the authenticated user (if manager)
3. **Find Employee Record**: Gets the user's associated employee record
4. **Get Active Employee Record**: Finds the active employee record (without end_date)
5. **Extract Designation**: Gets the designation from the active employee record
6. **Find Child Designations**: Recursively finds all child and descendant designations
7. **Find Managed Employees**: Gets all employees with active records having those designations
8. **Filter Query**: Applies the employee IDs filter to the model query

### Hierarchy Example

```
CEO (Current User)
├── VP Sales
│   ├── Sales Manager
│   └── Account Manager
└── VP Engineering
    ├── Engineering Manager
    └── Senior Developer
```

If the current user is CEO, they can manage all employees below them in the hierarchy.

### getManager() Function Process

The `getManager()` function works in reverse to find the manager of an employee:

1. **Get Target Employee**: If no employee ID is provided, uses the current authenticated user's employee
2. **Find Active Employee Record**: Gets the employee's active employee record (without end_date)
3. **Extract Current Designation**: Gets the designation from the active employee record
4. **Find Parent Designation**: Gets the parent designation of the current designation
5. **Find Manager Employee**: Finds the employee who has the parent designation as their active designation
6. **Return Manager**: Returns the Employee instance of the manager

#### getManager() Example

```
CEO
├── VP Sales (Manager of Sales Manager)
│   ├── Sales Manager (Current Employee)
│   └── Account Manager
└── VP Engineering
    ├── Engineering Manager
    └── Senior Developer
```

If you call `getManager()` for the "Sales Manager" employee, it will:
1. Find that Sales Manager has the "Sales Manager" designation
2. Find the parent designation is "VP Sales"
3. Find the employee who has the "VP Sales" designation
4. Return that employee as the manager

## Error Handling

The trait includes comprehensive error handling:

- **Column Validation**: Throws exception if `employee_id` column doesn't exist
- **Null Checks**: Handles cases where user has no employee record
- **Empty Results**: Returns empty collections when no managed employees exist
- **Exception Logging**: Logs errors for debugging purposes

### Exception Messages

```php
// If employee_id column is missing
"Model {table_name} must have an 'employee_id' column to use HasManager trait."
```

## Examples

### Leave Allocation Management

```php
// In LeaveAllocation model
class LeaveAllocation extends Model
{
    use HasManager;
    
    protected $fillable = ['employee_id', 'start_date', 'end_date'];
}

// Usage in controller
public function index()
{
    // Manager can only see leave allocations for their subordinates
    // Non-manager users will see all leave allocations
    $leaveAllocations = LeaveAllocation::forManager()
        ->with('employee')
        ->orderBy('start_date', 'desc')
        ->get();
        
    return view('leave-allocations.index', compact('leaveAllocations'));
}
```

### Attendance Management

```php
// In Attendance model
class Attendance extends Model
{
    use HasManager;
    
    protected $fillable = ['employee_id', 'date', 'check_in', 'check_out'];
}

// Usage in controller
public function todayAttendance()
{
    // Manager can only see attendance for their subordinates
    // Non-manager users will see all attendance records
    $todayAttendance = Attendance::forManager()
        ->whereDate('date', today())
        ->with('employee')
        ->get();
        
    return response()->json($todayAttendance);
}
```

### Finding Employee Manager

```php
// In Employee model (already has HasManager trait)
// Usage in controller
public function getEmployeeDetails($employeeId)
{
    $employee = Employee::find($employeeId);
    
    if (!$employee) {
        return response()->json(['error' => 'Employee not found'], 404);
    }
    
    // Get the employee's manager
    $manager = $employee->getManager();
    
    $response = [
        'employee' => $employee,
        'manager' => $manager ? [
            'id' => $manager->id,
            'full_name' => $manager->full_name,
            'email' => $manager->email,
            'emp_id' => $manager->emp_id,
        ] : null
    ];
    
    return response()->json($response);
}

// Usage in Blade template
@if($employee->getManager())
    <p><strong>Reports to:</strong> {{ $employee->getManager()->full_name }}</p>
    <p><strong>Manager Email:</strong> {{ $employee->getManager()->email }}</p>
@else
    <p><em>This employee has no direct manager (top-level position)</em></p>
@endif

// Usage in API Resource
public function toArray($request)
{
    return [
        'id' => $this->id,
        'full_name' => $this->full_name,
        'emp_id' => $this->emp_id,
        'email' => $this->email,
        'manager' => $this->getManager() ? [
            'id' => $this->getManager()->id,
            'full_name' => $this->getManager()->full_name,
            'emp_id' => $this->getManager()->emp_id,
        ] : null,
    ];
}
```

## Performance Considerations

### Optimization Tips

1. **Eager Loading**: Always eager load relationships to avoid N+1 queries
2. **Caching**: Consider caching designation hierarchies for frequent access
3. **Database Indexes**: Ensure proper indexes on `employee_id` and `designation_id` columns

### Query Performance

```php
// Good: Eager loading relationships
$records = YourModel::forManager()
    ->with(['employee.employeeRecords.designation', 'employee.user'])
    ->get();

// Consider: Caching for heavy usage
$managedEmployeeIds = Cache::remember(
    "managed_employees_" . auth()->id(),
    3600, // 1 hour
    fn() => $model->getCurrentUserManagedEmployeeIds()
);
```

## Testing

### Unit Test Example

```php
public function test_for_manager_scope_filters_correctly()
{
    // Create test data
    $manager = User::factory()->create();
    $managerEmployee = Employee::factory()->create(['user_id' => $manager->id]);
    $subordinateEmployee = Employee::factory()->create();
    
    // Create designations hierarchy
    $managerDesignation = Designation::factory()->create(['name' => 'Manager']);
    $subordinateDesignation = Designation::factory()->create([
        'name' => 'Employee',
        'parent_id' => $managerDesignation->id
    ]);
    
    // Create employee records
    EmployeeRecord::factory()->create([
        'employee_id' => $managerEmployee->id,
        'designation_id' => $managerDesignation->id,
        'end_date' => null
    ]);
    
    EmployeeRecord::factory()->create([
        'employee_id' => $subordinateEmployee->id,
        'designation_id' => $subordinateDesignation->id,
        'end_date' => null
    ]);
    
    // Create test records
    $managerRecord = YourModel::factory()->create(['employee_id' => $managerEmployee->id]);
    $subordinateRecord = YourModel::factory()->create(['employee_id' => $subordinateEmployee->id]);
    
    // Test the scope
    $this->actingAs($manager);
    $results = YourModel::forManager()->get();
    
    $this->assertCount(1, $results);
    $this->assertEquals($subordinateRecord->id, $results->first()->id);
}
```

## Troubleshooting

### Common Issues

1. **Empty Results**: Check if the current user has an active employee record with designation
2. **No Filtering Applied**: Verify the current user's `user_type` is set to 'manager'
3. **Missing Relationships**: Ensure all required model relationships are properly defined
4. **Column Not Found**: Verify the model table has an `employee_id` column
5. **Performance Issues**: Add proper database indexes and use eager loading

### Debug Methods

```php
// Check current user's user type
$userType = auth()->user()->user_type ?? 'Not authenticated';
dd($userType);

// Check current user's designation
$designation = $model->getCurrentUserDesignation();
dd($designation);

// Check managed employee IDs
$employeeIds = $model->getCurrentUserManagedEmployeeIds();
dd($employeeIds);

// Check if user can manage specific employee
$canManage = $model->canManageEmployee(123);
dd($canManage);

// Check employee's manager
$manager = $employee->getManager();
dd($manager);

// Debug manager finding process
$employee = Employee::find(123);
$activeRecord = $employee->employeeRecords()->active()->with('designation')->first();
dd([
    'employee' => $employee,
    'active_record' => $activeRecord,
    'current_designation' => $activeRecord?->designation,
    'parent_designation' => $activeRecord?->designation?->parent,
    'manager' => $employee->getManager()
]);
```

## Migration Example

If you need to add `employee_id` to an existing model:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('your_table', function (Blueprint $table) {
            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->onDelete('cascade');
            $table->index('employee_id');
        });
    }

    public function down()
    {
        Schema::table('your_table', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
```
