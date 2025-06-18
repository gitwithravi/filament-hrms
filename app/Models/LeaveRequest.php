<?php

namespace App\Models;

use App\Enums\HalfDayShift;
use App\Enums\LeaveRequestStatus;
use App\Traits\HasOwnRecord;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveRequestFactory> */
    use HasFactory, HasUuid, HasOwnRecord;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'requester_user_id',
        'start_date',
        'end_date',
        'is_half_date',
        'half_day_shift',
        'reason',
        'alternate_arrangement',
        'status',
        'leave_file',
        'approver_user_id',
        'approver_comment',
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
    protected $hidden = ['created_at', 'updated_at'];

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
            'is_half_date' => 'boolean',
            'half_day_shift' => HalfDayShift::class,
            'status' => LeaveRequestStatus::class,
        ];
    }

    /**
     * Get the employee that owns the leave request.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the leave type for this request.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the user who requested the leave.
     */
    public function requesterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    /**
     * Get the user who approved/rejected the leave.
     */
    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    /**
     * Scope to get approved leave requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', LeaveRequestStatus::APPROVED);
    }

    /**
     * Scope to get pending leave requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', LeaveRequestStatus::REQUESTED);
    }

    /**
     * Scope to get rejected leave requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', LeaveRequestStatus::REJECTED);
    }

    /**
     * Check if the leave request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === LeaveRequestStatus::APPROVED;
    }

    /**
     * Check if the leave request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === LeaveRequestStatus::REQUESTED;
    }

    /**
     * Check if the leave request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === LeaveRequestStatus::REJECTED;
    }
}
