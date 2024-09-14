<?php
namespace Larapay\Core\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

trait HTTPRequest
{
  protected Response $response;
  protected array $headers = [];
  protected array $postData = [];
  protected int $code = 0;
  protected ?string $message = null;

  public function post($url, $postData, $headers): void
  {
    $this->response = Http::withHeaders($headers)->post($url, $postData);
    if(!$this->response->successful()){
      $this->error = $this->json()['message'] ?? __('Uknown error');
    }
    /*dd([
      'body' => $this->response->body(),
      'status' => $this->response->status(),
      'successful' => $this->response->successful(),
      'successful' => $this->response->successful(),
      'redirect' => $this->response->redirect(),
      'failed' => $this->response->failed(),
      'clientError' => $this->response->clientError(),
    ]);
    return true;*/
  }

  public function response(): Response
  {
    return $this->response;
  }
  public function status(): int
  {
    return $this->response->status();
  }

  public function json(): array
  {
    return $this->response->json();
  }

  public function body(): string
  {
    return $this->response->body();
  }
}