<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    /** @use HasFactory<\Database\Factories\DesignationFactory> */
    use HasFactory, HasUuid;

    // add fillable
    protected $fillable = ['name'];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Get the employees for the designation.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
