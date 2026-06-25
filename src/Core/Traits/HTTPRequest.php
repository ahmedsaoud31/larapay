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

  public function post($url, $data = [], $headers = [], $options = []): void
  {
    try{
      $this->response = Http::withHeaders($headers)
                              ->withOptions($options)
                              ->post($url, $data);
    }catch(Exception $e){
      $ex = new GatewayConnectionException($this->gateway);
      $this->error = $ex->msg;
      throw $ex;
    }
    $this->handleErrors();
  }

  public function get($url, $data = [], $headers = [], $options = []): void
  {
    try{
      $this->response = Http::withHeaders($headers)->get($url, $data);
    }catch(Exception $e){
      $ex = new GatewayConnectionException($this->gateway);
      $this->error = $ex->msg;
      throw $ex;
    }
    $this->handleErrors();
  }

  public function put($url, $data = [], $headers = [], $options = []): void
  {
    try{
      $this->response = Http::withHeaders($headers)->put($url, $data);
    }catch(Exception $e){
      $ex = new GatewayConnectionException($this->gateway);
      $this->error = $ex->msg;
      throw $ex;
    }
    $this->handleErrors();
  }

  public function response(): Response
  {
    return $this->response;
  }
  
  public function json(): object | null
  {
    $json = $this->response->object();
    if(is_array($json) > 0){
      return $json[0];
    }
    return $json;
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