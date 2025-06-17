<?php

namespace App\Models;

use App\Enums\WeekDay;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    use HasUuid;

    // add fillable
    protected $fillable = [
        'name',
        'description',
        'start_time',
        'end_time',
        'weekoffs',
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'weekoffs' => 'array',
    ];

    public function setWeekoffsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['weekoffs'] = json_encode($value);
        } else {
            $this->attributes['weekoffs'] = $value;
        }
    }

    public function getWeekoffsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getWeekoffsEnumAttribute()
    {
        return collect($this->weekoffs)->map(function ($day) {
            return WeekDay::tryFrom($day);
        })->filter()->values();
    }
}
