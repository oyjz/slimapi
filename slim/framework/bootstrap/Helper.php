<?php

namespace slim\bootstrap;

use slim\Api;

class Helper
{


    public function bootstrap(Api $api)
    {
        require SLIM_PATH . '\\framework\\support\\helpers.php';
    }
}