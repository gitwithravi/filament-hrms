<?php

namespace App\Models;

use App\Enums\HolidayType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    /** @use HasFactory<\Database\Factories\HolidayFactory> */
    use HasFactory, HasUuid;

    // add fillable
    protected $fillable = ['name', 'from_date', 'to_date', 'holiday_type'];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'holiday_type' => HolidayType::class,
        ];
    }
}
