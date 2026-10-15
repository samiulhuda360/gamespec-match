<?php

namespace App\Providers;

use App\Ai\CallLog;
use App\Ai\LlmClient;
use App\Ai\OfflineClient;
use App\Ai\OpenAiCompatibleClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // A live client when a key is configured, otherwise one that refuses every call so
        // each feature uses its rule-based path (see config/ai.php).
        $this->app->singleton(LlmClient::class, function ($app) {
            $c = config('ai');
            $live = $c['driver'] === 'live' || ($c['driver'] === 'auto' && filled($c['api_key']));

            return $live
                ? new OpenAiCompatibleClient($c['base_url'], (string) $c['api_key'], $c['chat_model'],
                    $c['embedding_model'], $c['timeout'], $c['cache_days'], $app->make(CallLog::class))
                : new OfflineClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Endpoints that may call a model: per user, or per IP for guests.
        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(15)->by($request->user()?->id ?: $request->ip()));
    }
}
