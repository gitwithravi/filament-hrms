<?php

namespace App\Services;

use Illuminate\Support\Facades\Log as LaravelLog;

class CustomLogService
{
    /**
     * Log an emergency message to the logs.
     *
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public static function emergency($message, array $context = [])
    {
        LaravelLog::emergency($message, $context);
    }

    /**
     * Log an alert message to the logs.
     *
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public static function alert($message, array $context = [])
    {
        LaravelLog::alert($message, $context);
    }

    /**
     * Log a critical message to the logs.
     *
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public static function critical($message, array $context = [])
    {
        LaravelLog::critical($message, $context);
    }

    /**
     * Log an error message to the logs.
     *
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public static function error($message, array $context = [])
    {
        LaravelLog::error($message, $context);
    }

    /**
     * Log a warning message to the logs.
     *
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public static function warning($message, array $context = [])
    {
        LaravelLog::warning($message, $context);
    }

    /**
     * Log a notice message to the logs.
     *
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public static function notice($message, array $context = [])
    {
        LaravelLog::notice($message, $context);
    }

    /**
     * Log an info message to the logs (disabled in production).
     *
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public static function info($message, array $context = [])
    {
        // Only log info messages if not in production environment
        if (!app()->environment('production')) {
            LaravelLog::info($message, $context);
        }
    }

    /**
     * Log a debug message to the logs.
     *
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public static function debug($message, array $context = [])
    {
        LaravelLog::debug($message, $context);
    }

    /**
     * Log a message with the given level.
     *
     * @param  mixed  $level
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public static function log($level, $message, array $context = [])
    {
        // If trying to log info level in production, skip it
        if ($level === 'info' && app()->environment('production')) {
            return;
        }

        LaravelLog::log($level, $message, $context);
    }

    /**
     * Dynamically proxy other method calls to the Laravel Log facade.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        return LaravelLog::$method(...$arguments);
    }
}
