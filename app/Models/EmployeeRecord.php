<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRecord extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeRecordFactory> */
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'department_id',
        'designation_id',
        'start_date',
        'end_date',
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
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Get the employee that this record belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the department that this record belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the designation that this record belongs to.
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Scope to get active records (without end date).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('end_date');
    }

    /**
     * Scope to get records that have ended.
     */
    public function scopeEnded($query)
    {
        return $query->whereNotNull('end_date');
    }

    /**
     * Check if this record is currently active.
     */
    public function isActive(): bool
    {
        return is_null($this->end_date);
    }
}
