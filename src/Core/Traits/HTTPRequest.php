<?php
namespace Larapay\Core\Traits;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Larapay\Core\Exceptions\GatewayConnectionException;
use Larapay\Core\Traits\Errors;

trait HTTPRequest
{
  use Errors;
  protected Response $response;
  protected ?string $redirect = null;

  public function post($url, $postData = [], $headers = [], $options = []): void
  {
    try{
      $this->response = Http::withHeaders($headers)
                              ->withOptions($options)
                              ->post($url, $postData);
    }catch(Exception $e){
      throw new GatewayConnectionException($this->gateway);
    }
    
    if(!$this->response->successful()){
      $this->handleErrors();
    }
  }

  public function get($url, $postData = [], $headers = [], $options = []): void
  {
    try{
      $this->response = Http::withHeaders($headers)->get($url, $postData);
    }catch(Exception $e){
      throw new GatewayConnectionException($this->gateway);
    }
    
    if(!$this->response->successful()){
      $this->handleErrors();
    }
  }

  public function response(): Response
  {
    return $this->response;
  }
  
  public function json(): object | null
  {
    if(is_array($this->response->object()) > 0){
      return $this->response->object()[0];
    }
    return $this->response->object();
  }

  public function hasRedirect(): bool
  {
    return $this->redirect ? true : false;
  }

  public function getRedirect(): string
  {
    return $this->redirect;
  }
}