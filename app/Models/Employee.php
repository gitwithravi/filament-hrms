<?php

namespace App\Models;

use App\Enums\Gender;
use App\Traits\HasUuid;
use App\Enums\Salutation;
use App\Traits\HasOwnRecord;
use App\Traits\HasManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, HasUuid, HasOwnRecord, HasManager;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'salutation',
        'full_name',
        'emp_id',
        'employee_category_id',
        'user_id',
        'dob',
        'date_of_joining',
        'date_of_leaving',
        'aadhaar_number',
        'pan_number',
        'gender',
        'contact_number',
        'email',
        'blood_group',
        'marital_status',
        'address',
        'fathers_name',
        'mothers_name',
        'emergency_contact_no',
        'photograph',
    ];

    /**
     * The attributes that should be guarded.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id', 'uuid'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salutation' => Salutation::class,
            'gender' => Gender::class,
            'dob' => 'date',
            'date_of_joining' => 'date',
            'date_of_leaving' => 'date',
        ];
    }

    /**
     * Get the employee category that the employee has.
     */
    public function employeeCategory(): BelongsTo
    {
        return $this->belongsTo(EmployeeCategory::class);
    }




    /**
     * Get the leave allocations for the employee.
     */
    public function leaveAllocations(): HasMany
    {
        return $this->hasMany(LeaveAllocation::class);
    }

    /**
     * Get the employee's full name with salutation.
     */
    public function getFullNameWithSalutationAttribute(): string
    {
        return $this->salutation->value . ' ' . $this->full_name;
    }

    /**
     * Scope to get active employees (not left).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('date_of_leaving');
    }

    /**
     * Get the employee records for the employee.
     */
    public function employeeRecords(): HasMany
    {
        return $this->hasMany(EmployeeRecord::class);
    }

    /**
     * Get the work shifts that the employee has.
     */
    public function workShifts(): BelongsToMany
    {
        return $this->belongsToMany(WorkShift::class, 'employee_work_shift')
                    ->withPivot(['start_date', 'end_date'])
                    ->withTimestamps();
    }

    /**
     * Get the leave requests for the employee.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get the attendance records for the employee.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the user associated with the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
