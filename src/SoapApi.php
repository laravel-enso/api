<?php

namespace LaravelEnso\Api;

use LaravelEnso\Api\Contracts\Client;
use LaravelEnso\Api\Contracts\Retry;
use LaravelEnso\Api\Contracts\SoapEndpoint;
use LaravelEnso\Api\Contracts\SoapHeaders;
use SoapClient;
use SoapFault;

class SoapApi implements Client
{
    protected int $tries;

    public function __construct(protected SoapEndpoint $endpoint)
    {
        $this->tries = 0;
    }

    public function call(): SoapResponse
    {
        $this->tries++;

        try {
            return new SoapResponse(
                $this->client()->__soapCall(
                    $this->endpoint->operation(),
                    $this->endpoint->arguments(),
                )
            );
        } catch (SoapFault $exception) {
            if ($this->shouldRetry()) {
                sleep($this->endpoint->delay());

                return $this->call();
            }

            return new SoapResponse(exception: $exception);
        }
    }

    public function tries(): int
    {
        return $this->tries;
    }

    protected function client(): SoapClient
    {
        $client = new SoapClient($this->endpoint->wsdl(), $this->endpoint->options());

        if ($this->endpoint instanceof SoapHeaders) {
            $client->__setSoapHeaders($this->endpoint->soapHeaders());
        }

        return $client;
    }

    protected function shouldRetry(): bool
    {
        return $this->endpoint instanceof Retry
            && $this->tries < $this->endpoint->tries();
    }
}
