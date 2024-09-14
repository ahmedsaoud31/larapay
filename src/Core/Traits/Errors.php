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
}