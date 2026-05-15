<?php

namespace LaravelEnso\Api;

use SoapFault;

class SoapResponse
{
    public function __construct(
        private mixed $body = null,
        private ?SoapFault $exception = null,
    ) {
    }

    public function successful(): bool
    {
        return !$this->failed();
    }

    public function failed(): bool
    {
        return $this->exception !== null;
    }

    public function status(): int
    {
        return $this->failed() ? 500 : 200;
    }

    public function body(): mixed
    {
        return $this->body;
    }

    public function exception(): ?SoapFault
    {
        return $this->exception;
    }

    public function code(): int|string
    {
        return $this->exception?->faultcode
            ?? $this->exception?->getCode()
            ?? $this->status();
    }

    public function message(): string
    {
        $message = $this->exception?->getMessage() ?? json_encode($this->body);

        return $message ?: '';
    }

    public function throw(): self
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this;
    }
}
