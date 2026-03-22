<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\DownstreamApiService;
use App\Services\Lti11Handler;
use App\Services\Lti13Handler;
use App\Services\MetricsService;
use App\Services\StudentIdResolver;
use App\Services\UserMappingService;
use Illuminate\Support\ServiceProvider;

class LtiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StudentIdResolver::class);
        $this->app->singleton(DownstreamApiService::class);
        $this->app->singleton(Lti13Handler::class);
        $this->app->singleton(Lti11Handler::class);
        $this->app->singleton(MetricsService::class);

        $this->app->singleton(UserMappingService::class, function ($app) {
            return new UserMappingService(
                $app->make(DownstreamApiService::class)
            );
        });
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/lti.php', 'lti');
    }
}
