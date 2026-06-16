<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Azure\AzureExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->loadDynamicSlaSettings();
    }

    /**
     * Load dynamic SLA settings from the database and override the static config,
     * safely wrapped to prevent blocking during installation or Artisan migrations.
     */
    protected function loadDynamicSlaSettings(): void
    {
        try {
            if (Schema::hasTable('settings')) {
                $slaSettings = Cache::rememberForever('sla_settings', fn (): array => [
                    'critical' => (int) (Setting::get('sla_critical_days') ?? config('sla.critical')),
                    'high' => (int) (Setting::get('sla_high_days') ?? config('sla.high')),
                    'medium' => (int) (Setting::get('sla_medium_days') ?? config('sla.medium')),
                    'low' => (int) (Setting::get('sla_low_days') ?? config('sla.low')),
                ]);

                config([
                    'sla.critical' => $slaSettings['critical'],
                    'sla.high' => $slaSettings['high'],
                    'sla.medium' => $slaSettings['medium'],
                    'sla.low' => $slaSettings['low'],
                ]);
            }
        } catch (\Throwable) {
            // Prevent blocking Artisan commands when the database is not set up yet
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn (): ?Password => $this->productionPassword());

        Event::listen(SocialiteWasCalled::class, AzureExtendSocialite::class);
    }

    protected function productionPassword(): ?Password
    {
        if (! app()->isProduction()) {
            return null;
        }

        return Password::min(12)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols()
            ->uncompromised();
    }
}
