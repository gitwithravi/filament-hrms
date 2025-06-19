<?php

namespace App\Facades;

use App\Services\CustomLogService;
use Illuminate\Support\Facades\Facade;

class Log extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return CustomLogService::class;
    }

    /**
     * Resolve the facade root instance from the container.
     *
     * @param  string  $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        return new CustomLogService;
    }
}
