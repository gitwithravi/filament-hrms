<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveAllocationRecord extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveAllocationRecordFactory> */
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'leave_allocation_id',
        'leave_type_id',
        'allotted',
        'used',
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
            'allotted' => 'decimal:2',
            'used' => 'decimal:2',
        ];
    }

    /**
     * Get the leave allocation that owns this record.
     */
    public function leaveAllocation(): BelongsTo
    {
        return $this->belongsTo(LeaveAllocation::class);
    }

    /**
     * Get the leave type that this record belongs to.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the remaining leave balance.
     */
    public function getRemainingAttribute(): float
    {
        return $this->allotted - $this->used;
    }

    /**
     * Check if the leave record has remaining balance.
     */
    public function hasRemaining(): bool
    {
        return $this->remaining > 0;
    }
}
