<?php

namespace Larapay\Tests;

use Larapay\LarapayServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LarapayServiceProvider::class,
        ];
    }
}
