<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use App\Traits\HasOwnRecord;

class LeaveAllocation extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveAllocationFactory> */
    use HasFactory, HasUuid, HasOwnRecord;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'description',
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
     * Get the employee that owns the leave allocation.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the leave allocation records for this allocation.
     */
    public function leaveAllocationRecords(): HasMany
    {
        return $this->hasMany(LeaveAllocationRecord::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (LeaveAllocation $leaveAllocation) {
            if ($leaveAllocation->hasOverlappingAllocation()) {
                throw ValidationException::withMessages([
                    'start_date' => 'This employee already has a leave allocation that overlaps with the selected period.',
                ]);
            }
        });

        static::updating(function (LeaveAllocation $leaveAllocation) {
            if ($leaveAllocation->hasOverlappingAllocation()) {
                throw ValidationException::withMessages([
                    'start_date' => 'This employee already has a leave allocation that overlaps with the selected period.',
                ]);
            }
        });
    }

    /**
     * Check if this allocation has overlapping allocations for the same employee.
     */
    public function hasOverlappingAllocation(): bool
    {
        $query = static::where('employee_id', $this->employee_id)
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('start_date', '<=', $this->end_date)
                             ->where('end_date', '>=', $this->start_date);
                });
            });

        // Exclude current record when updating
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }
}
