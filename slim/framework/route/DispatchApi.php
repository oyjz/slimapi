<?php

namespace slim\route;

use slim\http\Request;

class DispatchApi extends Dispatch
{

    public function dispatch(Request $request)
    {
        $method       = strtolower($request->method());
        $dispatch_key = $this->api->make('config')->get('api.dispatch_key');
        $dispatch_val = $request->$method($dispatch_key);

        return $dispatch_val;
    }
}