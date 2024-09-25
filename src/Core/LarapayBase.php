<?php

namespace Larapay\Core;

use Larapay\Core\Traits\Errors;
use Larapay\Core\Traits\HTTPRequest;

class LarapayBase
{   
  use Errors, HTTPRequest;

  protected ?string $uid = null;

  public function __construct()
  {
    $this->uid = uniqid();
  }
  
  public function getGateway() : string 
  {
    return $this->gateway;
  }

  public function getServerCallback() : string 
  {
    return $this->addParmToURL($this->server_callback, 'uid', $this->uid);
  }

  public function getClientCallback() : string 
  {
    return $this->addParmToURL($this->client_callback, 'uid', $this->uid);
  }

  private function addParmToURL(?string $url, string $key, ?string $value): string
  {
    if(!$value) return $url;
    if(strpos($url, '?')){
      return "{$url}&{$key}={$value}";
    }else{
      return "{$url}?{$key}={$value}";
    }
  }

  protected function getEndPoint($path, $params = []) : string
  {
    $endpoint = '';
    if(str_ends_with($this->endpoint, '/')){
      $endpoint = "{$this->endpoint}{$path}";
    }else{
      $endpoint = "{$this->endpoint}/{$path}";
    }
    if($params){
      $params = http_build_query($params);
      $endpoint = "{$endpoint}?{$params}";
    }
    return $endpoint;
  }
}
