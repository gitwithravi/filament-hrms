<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCategory extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeCategoryFactory> */
    use HasFactory, HasUuid;

    // add fillable
    protected $fillable = ['name'];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Get the employees for the employee category.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
