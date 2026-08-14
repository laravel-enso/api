<?php

namespace LaravelEnso\Api;

use Exception;
use LaravelEnso\Api\Contracts\Retry;
use LaravelEnso\Api\Contracts\ServiceAddress;
use LaravelEnso\Api\Exceptions\Remote;
use LaravelEnso\Helpers\Exceptions\EnsoException;
use SoapClient;
use SoapFault;
use stdClass;

class Service
{
    protected int $tries;

    public function __construct(protected ServiceAddress $serviceAddress)
    {
        $this->tries = 0;
    }

    public function call(): stdClass
    {
        $this->tries++;

        try {
            return $this->response();
        } catch (Exception $exception) {
            if ($this->shouldRetry()) {
                sleep($this->serviceAddress->delay());

                return $this->call();
            }

            throw $exception;
        }
    }

    public function tries(): int
    {
        return $this->tries;
    }

    protected function response(): stdClass
    {
        try {
            $client = new SoapClient($this->serviceAddress->url());
            $response = $client->__soapCall(
                $this->serviceAddress->operation(),
                [$this->serviceAddress->params()]
            );

            return $response;
        } catch (SoapFault $fault) {
            throw Remote::error($fault->getMessage());
        } catch (Exception $e) {
            throw new EnsoException($e->getMessage(), $e->getCode());
        }
    }

    protected function shouldRetry(): bool
    {
        return $this->serviceAddress instanceof Retry
            && $this->tries < $this->serviceAddress->tries();
    }
}
