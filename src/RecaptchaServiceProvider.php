<?php

namespace VDVT\Recaptcha;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use VDVT\Recaptcha\ReCaptchaBuilder;

class RecaptchaServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'vdvt/recaptcha');

        $this->registerRoutes();

        Validator::extendImplicit(ReCaptchaBuilder::DEFAULT_RECAPTCHA_RULE_NAME, function ($attribute, $value) {
            return app('recaptcha')->validate($value);
        }, trans('vdvt/validation::validation.recaptcha'));

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
        ], 'vdvt');

        // Publishing the translation files.
        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/vdvt/recaptcha'),
        ], 'vdvt');
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
