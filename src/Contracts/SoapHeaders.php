<?php

namespace LaravelEnso\Api\Contracts;

use SoapHeader;

interface SoapHeaders
{
    public function soapHeaders(): SoapHeader|array|null;
}
