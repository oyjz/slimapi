<?php

namespace slim\base;


abstract class Services
{
    /**
     * The application instance.
     *
     * @var \slim\Api
     */
    protected $app;

    /**
     * Create a new service provider instance.
     *
     * @param  \slim\Api  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

}