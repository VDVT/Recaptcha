<?php

namespace VDVT\Recaptcha;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RecaptchaServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerRoutes();

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/recaptcha.php', 'vdvt.recaptcha.recaptcha');

        // Register the service the package provides.
        $this->app->singleton('recaptcha', function ($app) {
            return new Recaptcha(
                config('vdvt.recaptcha.recaptcha.api_site_key'),
                config('vdvt.recaptcha.recaptcha.api_secret_key')
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['recaptcha'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/recaptcha.php' => config_path('vdvt/recaptcha/recaptcha.php'),
        ], 'recaptcha.config');
    }

    /**
     * @return ReCaptchaServiceProvider
     *
     * @since v3.4.1
     */
    protected function registerRoutes(): ReCaptchaServiceProvider
    {
        Route::get(
            config('vdvt.recaptcha.recaptcha.default_validation_route', 'vdvt/recaptcha/validate'),
            ['uses' => 'VDVT\Recaptcha\Controllers\ReCaptchaController@validateV3']
        )->middleware('web');

        return $this;
    }
}
