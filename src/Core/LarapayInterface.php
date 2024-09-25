<?php

namespace Larapay\Core;

interface LarapayInterface
{

  public function __construct(string $gateway, string $mode);

  public function init();

  public function set();

}
