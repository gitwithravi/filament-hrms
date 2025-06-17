<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the HasUuid trait for a model.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getUuidColumn()})) {
                $model->{$model->getUuidColumn()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Initialize the HasUuid trait for an instance.
     */
    protected function initializeHasUuid(): void
    {
        if (!in_array($this->getUuidColumn(), $this->fillable)) {
            $this->fillable[] = $this->getUuidColumn();
        }
    }

    /**
     * Get the column name for the UUID.
     */
    public function getUuidColumn(): string
    {
        return 'uuid';
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return $this->getUuidColumn();
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($this->getUuidColumn(), $value)->first();
    }

    /**
     * Retrieve the child model for a bound value.
     */
    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return parent::resolveChildRouteBinding($childType, $value, $field);
    }

    /**
     * Find a model by its UUID.
     */
    public static function findByUuid(string $uuid): ?Model
    {
        return static::where((new static)->getUuidColumn(), $uuid)->first();
    }

    /**
     * Find a model by its UUID or fail.
     */
    public static function findByUuidOrFail(string $uuid): Model
    {
        return static::where((new static)->getUuidColumn(), $uuid)->firstOrFail();
    }

    /**
     * Scope a query to only include models with the given UUID.
     */
    public function scopeWhereUuid($query, string $uuid)
    {
        return $query->where($this->getUuidColumn(), $uuid);
    }

    /**
     * Get the UUID attribute.
     */
    public function getUuidAttribute(): ?string
    {
        $column = $this->getUuidColumn();
        return $this->attributes[$column] ?? null;
    }
}
