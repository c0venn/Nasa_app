<?php

namespace App\Providers;

use App\Contracts\NasaServiceInterface;
use App\Services\NasaService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        $this->app->bind(NasaServiceInterface::class, NasaService::class);
    }

   
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('nasa-api', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(function () {
                    return Response::json([
                        'status' => 'error',
                        'message' => 'Too many requests. Please wait before retrying.',
                        'retry_after' => RateLimiter::availableIn('nasa-api-' . request()->ip())
                    ], 429);
                });
        });
    }
}
