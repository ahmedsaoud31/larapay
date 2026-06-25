<?php
namespace Larapay\Core\Traits;

trait Errors
{
  protected string|bool $error = false;
  protected array $errors = [];

  public function getError(): ?string
  {
    return $this->error ?: $this->errors[0] ?? null;
  }

  public function getErrors(): array
  {
    return $this->errors;
  }

  public function hasError(): bool
  {
    return $this->error || count($this->errors) ? true : false;
  }

  private function handleErrors() : void
  {
    if(!$this->response->successful()){
      $this->error = $this->json()->message ?? false;
      if($this->json()){
        foreach($this->json() as $key => $value){
          if(is_array($value) && isset($value[0])){
            $this->errors[] = "{$key}: {$value[0]}";
          }
          if(is_object($value) && isset($value->cause)){
            $this->errors[] = "{$value->cause}";
          }
        }
      }
      if($this->response->status() != 200){
        $this->error = __("{$this->response->status()}: {$this->response->reason()}");
      }
      if(!$this->hasError()){
        $this->error = __('Uknown error');
      }
    } else if($this->response->status() != 200){
      $this->error = __("{$this->response->status()}: {$this->response->reason()}");
    }
  }
}