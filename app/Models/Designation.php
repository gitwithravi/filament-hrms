<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Designation extends Model
{
    /** @use HasFactory<\Database\Factories\DesignationFactory> */
    use HasFactory, HasUuid;

    // add fillable
    protected $fillable = ['name', 'parent_id'];
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

    /**
     * Get the parent designation.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'parent_id');
    }

    /**
     * Get the child designations.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Designation::class, 'parent_id');
    }

    /**
     * Get all descendants (children, grandchildren, etc.) recursively.
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(Designation::class, 'parent_id')->with('descendants');
    }

    /**
     * Get all ancestors (parent, grandparent, etc.) recursively.
     */
    public function ancestors()
    {
        return $this->parent() ? $this->parent->ancestors()->prepend($this->parent) : collect();
    }

    /**
     * Check if this designation is a root designation (has no parent).
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if this designation is a leaf designation (has no children).
     */
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * Get the depth level of this designation in the hierarchy.
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }
}
