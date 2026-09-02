<?php

namespace Larapay;

use Illuminate\Support\Manager;
use Larapay\Core\Gateways\Kashier;
use Larapay\Core\Gateways\Payfort;
use Larapay\Core\Gateways\PayPal;
use Larapay\Core\Gateways\PayTabs;
use Larapay\Core\Gateways\PayMob;
use Larapay\Core\Gateways\Tab;

class LarapayManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('larapay.gateway') ?: 'paypal';
    }

    /**
     * Create an instance of the PayPal driver.
     */
    protected function createPaypalDriver()
    {
        return (new PayPal(gateway: 'paypal', mode: $this->getMode()))->init();
    }

    /**
     * Create an instance of the PayTabs driver.
     */
    protected function createPaytabsDriver()
    {
        return (new PayTabs(gateway: 'paytabs', mode: $this->getMode()))->init();
    }

    /**
     * Create an instance of the PayMob driver.
     */
    protected function createPaymobDriver()
    {
        return (new PayMob(gateway: 'paymob', mode: $this->getMode()))->init();
    }

    /**
     * Create an instance of the Kashier driver.
     */
    protected function createKashierDriver()
    {
        return (new Kashier(gateway: 'kashier', mode: $this->getMode()))->init();
    }

    /**
     * Create an instance of the Payfort driver.
     */
    protected function createPayfortDriver()
    {
        return (new Payfort(gateway: 'payfort', mode: $this->getMode()))->init();
    }

    /**
     * Create an instance of the Tab driver.
     */
    protected function createTabDriver()
    {
        return (new Tab(gateway: 'tab', mode: $this->getMode()))->init();
    }

    /**
     * Get the mode from configuration (live or sandbox).
     */
    protected function getMode()
    {
        return $this->config->get('larapay.mode') ?: 'sandbox';
    }

    /**
     * Backward compatibility with init()
     * Allows Larapay::init('paypal') to work identically.
     */
    public function init($gateway = null, $mode = null)
    {
        if ($mode) {
            $this->config->set('larapay.mode', $mode);
        }
        
        return $this->driver($gateway);
    }
}
