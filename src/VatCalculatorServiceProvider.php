<?php

namespace Machatschek\VatCalculator;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;

class VatCalculatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/vat_calculator.php' => config_path('vat_calculator.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/vat_calculator.php', 'vat_calculator'
        );

        $this->registerVatCalculator();
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    protected function registerVatCalculator()
    {
        $this->app->bind('vatcalculator', VatCalculator::class);

        $this->app->bind(VatCalculator::class, function ($app) {
            $config = $app->make(Repository::class);

            return new VatCalculator($config);
        });
    }
}
