<?php

namespace slim\route;

use slim\http\Request;

class DispatchWeb extends Dispatch
{

    public function dispatch(Request $request)
    {
        $pathinfo = strtolower($request->pathinfo());

        return $pathinfo;
    }
}