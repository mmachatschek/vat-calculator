<?php

namespace Machatschek\VatCalculator\Tests;

use Machatschek\VatCalculator\Facades\VatCalculator;
use Machatschek\VatCalculator\VatCalculatorServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            VatCalculatorServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'VatCalculator' => VatCalculator::class,
        ];
    }
}
