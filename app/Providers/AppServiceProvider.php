<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Support\ServiceProvider;
use App\Services\CaptionGeneratorService;
use App\Services\ImageGeneratorService;
use App\Services\GroqService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    $this->app->singleton(GroqService::class);

    $this->app->bind(CaptionGeneratorService::class, function ($app) {
        return new CaptionGeneratorService($app->make(GroqService::class));
    });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            if (!Auth::check()) {
                return;
            }

            $userId = Auth::id();

            $unreadCount = cache()->remember(
                "user_{$userId}_unread_count",
                now()->addMinutes(5),
                fn () => Notification::where('user_id', $userId)
                    ->where('is_read', 0)
                    ->count()
            );

            $view->with('unreadCount', $unreadCount);
        });
    }
}
