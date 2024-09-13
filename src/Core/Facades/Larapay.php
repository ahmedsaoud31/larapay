<?php

namespace Larapay\Facades;

use Illuminate\Support\Facades\Facade;
use Larapay\Core\Larapay as LarapayCore;

class Larapay extends Facade
{
    protected static function getFacadeAccessor()
    {
        return LarapayCore::class;
    }
}