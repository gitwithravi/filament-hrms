# LeaveCountUpdateService Documentation

## Overview

The `LeaveCountUpdateService` is responsible for automatically updating the `used` column in the `leave_allocation_records` table based on operations performed on the `LeaveRequest` model. This service ensures that leave balances are always accurate and up-to-date.

## Features

- **Automatic Updates**: Automatically updates used leave counts when leave requests are created, updated, or deleted
- **Status Change Handling**: Handles leave request status changes (approved, rejected, withdrawn)
- **Date Change Handling**: Recalculates used counts when leave dates are modified
- **Transaction Safety**: All operations are wrapped in database transactions
- **Data Consistency**: Provides methods to recalculate and sync leave counts
- **Working Day Calculation**: Uses the existing `WorkingDayService` and `LeaveBalanceService` for accurate calculations

## How It Works

### Automatic Operation via Observer

The service is automatically triggered through the `LeaveRequestObserver` which listens to:

1. **LeaveRequest Created**: When a new leave request is created, if it's approved, the used count is incremented
2. **LeaveRequest Updated**: When a leave request is updated, handles status changes and date changes
3. **LeaveRequest Deleted**: When a leave request is deleted, if it was approved, the used count is decremented

### Status Change Logic

- **Approved → Rejected/Withdrawn**: Subtracts used days from the allocation
- **Rejected/Withdrawn → Approved**: Adds used days to the allocation
- **Pending → Approved**: Adds used days to the allocation
- **Approved → Pending**: Subtracts used days from the allocation

### Date Change Logic

When dates are changed for an approved leave request:
1. Subtracts the old date range from used count
2. Adds the new date range to used count

## Manual Usage

### Injecting the Service

```php
use App\Services\LeaveCountUpdateService;

public function __construct(
    private LeaveCountUpdateService $leaveCountUpdateService
) {}
```

### Manual Status Change Handling

```php
// Handle status change manually
$this->leaveCountUpdateService->handleLeaveRequestStatusChange(
    $leaveRequest,
    $oldStatus // LeaveRequestStatus enum or null
);
```

### Manual Date Change Handling

```php
// Handle date changes manually
$this->leaveCountUpdateService->handleLeaveRequestDatesUpdate(
    $leaveRequest,
    $oldStartDate,    // Carbon instance
    $oldEndDate,      // Carbon instance
    $oldIsHalfDay     // boolean
);
```

### Manual Deletion Handling

```php
// Handle deletion manually
$this->leaveCountUpdateService->handleLeaveRequestDeletion($leaveRequest);
```

### Data Consistency Operations

```php
// Recalculate all leave counts for a specific employee
$this->leaveCountUpdateService->recalculateUsedCountsForEmployee($employeeId);
```

## Console Commands

### Recalculate Leave Counts

The service provides a console command for recalculating leave counts:

```bash
# Recalculate for all employees
php artisan leave:recalculate-counts --all

# Recalculate for a specific employee
php artisan leave:recalculate-counts --employee=123
```

This command is useful for:
- Data migration scenarios
- Fixing data inconsistencies
- Initial setup of the service

## Dependencies

The service depends on:
- `WorkingDayService`: For calculating working days
- `LeaveBalanceService`: For leave day calculations
- `LeaveAllocationRecord`: Model for leave allocation records
- `LeaveRequest`: Model for leave requests

## Database Requirements

The service expects the following database structure:

### leave_allocation_records table
- `id`: Primary key
- `leave_allocation_id`: Foreign key to leave_allocations
- `leave_type_id`: Foreign key to leave_types
- `allotted`: Decimal field for allocated leave days
- `used`: Decimal field for used leave days (updated by this service)

### leave_requests table
- `employee_id`: Foreign key to employees
- `leave_type_id`: Foreign key to leave_types
- `start_date`: Leave start date
- `end_date`: Leave end date
- `is_half_date`: Boolean for half-day leaves
- `status`: Leave request status enum

## Model Relationships

The service relies on these model relationships:

```php
// Employee model
public function leaveAllocations(): HasMany
public function leaveRequests(): HasMany
public function workShifts(): BelongsToMany

// LeaveAllocation model
public function leaveAllocationRecords(): HasMany
public function employee(): BelongsTo

// LeaveRequest model
public function employee(): BelongsTo
public function leaveType(): BelongsTo
```

## Error Handling

The service includes:
- Database transaction rollback on errors
- Validation to prevent negative used counts
- Graceful handling of missing relationships
- Fallback calculations when work shifts are not defined

## Performance Considerations

- Operations are batched in database transactions
- Queries are optimized with proper indexing
- The service only calculates changes, not full recalculations on every update
- Bulk operations are available for data consistency checks

## Testing

When testing, you can:
1. Mock the service in unit tests
2. Use database transactions in feature tests
3. Test with the console command for integration testing

## Troubleshooting

### Common Issues

1. **Negative used counts**: The service prevents this by setting minimum to 0
2. **Missing work shifts**: Falls back to simple day calculations
3. **Missing leave allocations**: Gracefully handles and logs warnings
4. **Observer not firing**: Ensure the observer is registered in AppServiceProvider

### Debugging

Enable query logging to see database operations:
```php
DB::enableQueryLog();
// ... perform operations
dd(DB::getQueryLog());
```

## Future Enhancements

Potential improvements:
- Queue-based processing for bulk operations
- Event-driven architecture for better decoupling
- Audit logging for leave count changes
- API endpoints for external integrations 
