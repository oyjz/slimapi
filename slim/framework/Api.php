<?php

namespace slim;

use app\Code;
use slim\doc\Doc;
use slim\lang\Lang;
use slim\http\Request;
use slim\http\Response;
use slim\route\Route;
use slim\logger\Logger;
use slim\container\Container;

class Api extends Container
{

    protected $version = '1.0.0';
    /**
     * Indicates if the application has been bootstrapped before.
     *
     * @var bool
     */
    protected $hasBeenBootstrapped = false;

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected $bootstrappers
        = [
            \slim\bootstrap\Helper::class,
            \slim\bootstrap\LoadConfig::class,
            \slim\bootstrap\Exception::class,
            \slim\bootstrap\Providers::class,
        ];

    /**
     * Create a new Illuminate application instance.
     *
     * @param  string|null $basePath
     *
     * @return void
     */
    public function __construct()
    {
        $this->registerBase();

        $this->bootstrap();

        $this->registerServices();

        //$this->registerCoreContainerAliases();
    }

    /**
     * registerBase
     */
    protected function registerBase()
    {
        $this->singleton('lang', function($api) {
            return new Lang($api);
        });
        $this->singleton('doc', function($api) {
            return new Doc($api);
        });
    }


    /**
     * registerServices
     */
    protected function registerServices()
    {
        static::setInstance($this);

        $this->instance('api', $this);

        $this->singleton('logger', function($api) {
            return new Logger($api);
        });

        $this->singleton('request', function($api) {
            return new Request($api);
        });

        $this->singleton('response', function($api, $data) {
            return new Response($api, $data);
        });

        $this->singleton('code', function() {
            return new Code;
        });

        $this->singleton('code', function() {
            return new Code;
        });


        /*
        $this->singleton('config', function ($api, $items) {
            return new Config($api, $items);
        });
        */
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @param  \slim\http\Request $request
     *
     * @return \slim\http\Response
     */
    public function run($request)
    {
        return $this->sendRequestThroughRouter($request);
    }

    /**
     * Send the given request through the middleware / router.
     *
     * @param  \slim\http\Request $request
     *
     * @return \slim\http\Response
     */
    protected function sendRequestThroughRouter($request)
    {
        $this->instance('request', $request);

        $this->bootstrap();

        return (new Route($this->api))->send();
    }

    /**
     *
     * bootstrap
     */
    public function bootstrap()
    {
        if (!$this->hasBeenBootstrapped) {
            $this->hasBeenBootstrapped = true;
            foreach ($this->bootstrappers as $bootstrapper) {
                $this->make($bootstrapper)->bootstrap($this);
            }
        }
    }


    /**
     * Resolve the given type from the container.
     *
     * (Overriding Container::make)
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        return parent::make($abstract, $parameters);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * (Overriding Container::bound)
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function bound($abstract)
    {
        return parent::bound($abstract);
    }

    /**
     * handleMessage
     *
     * @param $message
     *
     * @return string
     */
    public function handleMessage($message)
    {
        $lang = $this->make('lang');
        if (strpos($message, ':')) {
            $name    = strstr($message, ':', true);
            $message = $lang->has($name) ? $lang->get($name) . strstr($message, ':') : $message;
        } elseif (strpos($message, ',')) {
            $name    = strstr($message, ',', true);
            $message = $lang->has($name) ? $lang->get($name) . ':' . substr(strstr($message, ','), 1) : $message;
        } elseif ($lang->has($message)) {
            $message = $lang->get($message);
        }

        return $message;
    }

    /**
     * getMessageStatus
     *
     * @param $key
     *
     * @return mixed
     */
    public function getMessageStatus($key)
    {
        return $this->make('code')->getCode($key);
    }

    /**
     * getMessage
     *
     * @param $key
     *
     * @return mixed
     */
    public function getMessage($key)
    {
        return $this->make('code')->getMessage($key);
    }


    /**
     * Run the given array of bootstrap classes.
     *
     * @param  array $bootstrappers
     *
     * @return void
     */
    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->fire('bootstrapping: ' . $bootstrapper, [$this]);

            $this->make($bootstrapper)->bootstrap($this);

            $this['events']->fire('bootstrapped: ' . $bootstrapper, [$this]);
        }
    }

    /**
     * getVersion
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}