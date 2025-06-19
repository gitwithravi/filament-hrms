<?php

namespace App\Providers;

use App\Models\User;
use App\Models\LeaveRequest;
use App\Observers\LeaveRequestObserver;
use App\Services\CustomLogService;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Console\Commands\MakeModularFilamentResource;
use App\Console\Commands\RecalculateLeaveCountsCommand;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        parent::register();
        FilamentView::registerRenderHook('panels::body.end', fn(): string => Blade::render("@vite('resources/js/app.js')"));

         // Register custom commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeModularFilamentResource::class,
                RecalculateLeaveCountsCommand::class,
            ]);
        }

        // Register custom log service
        $this->app->singleton(CustomLogService::class, function ($app) {
            return new CustomLogService;
        });

        // Register refactored leave request services
        $this->registerLeaveRequestServices();
    }

    /**
     * Register leave request related services
     */
    private function registerLeaveRequestServices(): void
    {
        $this->app->singleton(\App\Services\WorkingDayService::class);
        $this->app->singleton(\App\Services\LeaveBalanceService::class);
        $this->app->singleton(\App\Services\LeaveOverlapService::class);
        $this->app->singleton(\App\Services\ConsecutiveLeaveValidationService::class);
        $this->app->singleton(\App\Services\LeaveRequestService::class);
        $this->app->singleton(\App\Services\LeaveCountUpdateService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Gate::define('viewApiDocs', function (User $user) {
            return true;
        });
        // Gate::policy()
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('discord', \SocialiteProviders\Google\Provider::class);
        });

        // Register model observers
        LeaveRequest::observe(LeaveRequestObserver::class);
    }
}
