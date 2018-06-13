<?php

namespace slim\route;

use slim\Api;

class Dispatch
{
    protected $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }
}