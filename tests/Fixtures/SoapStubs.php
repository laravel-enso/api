<?php

if (! class_exists(SoapClient::class)) {
    class SoapClient
    {
    }
}

if (! class_exists(SoapFault::class)) {
    class SoapFault extends Exception
    {
        public function __construct(string $faultcode, string $faultstring)
        {
            parent::__construct($faultstring);
        }
    }
}
