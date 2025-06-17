<?php

namespace App\Models;

use App\Enums\AttendanceCategory;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class AttendanceType extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'code',
        'alias',
        'category',
        'description',
    ];

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'category' => AttendanceCategory::class,
    ];
}
