<?php

namespace slim\route;

use Closure;
use slim\Api;
use slim\http\Response;

class Route
{
    /**
     * The api instance.
     *
     * @var \slim\Api
     */
    private $api;

    /**
     * The request instance.
     *
     * @var \slim\http\Request
     */
    private $request;

    /**
     * The request instance.
     *
     * @var \slim\http\Request
     */
    private $rules = [];

    /**
     * Create a new class instance.
     *
     * @param  \slim\Api|null $api
     *
     * @return void
     */
    public function __construct(Api $api = null)
    {
        $this->api     = $api;
        $this->request = $api->make('request');
    }

    /**
     * send
     *
     * @return Response
     */
    public function send()
    {
        $data = $this->register()->check();

        if (!is_array($data)) {
            throw new \InvalidArgumentException('response data invalid');
        }

        return (new Response($this->api, $data));
    }


    /**
     * check
     *
     * @return mixed
     * @throws \Exception
     */
    public function check()
    {
        $dispatch = $this->getDispatch($this->api->make('dispatch', [$this->api])->dispatch($this->request));

        if($dispatch instanceof Closure) {
            die($dispatch());
        }

        $class    = $dispatch[0];
        $action   = $dispatch[1];
        $instance = new $class;
        $vars     = [];
        if (is_callable([$instance, $action])) {
            // 执行操作方法
            $call = [$instance, $action];
        } elseif (is_callable([$instance, '_empty'])) {
            // 空操作
            $call = [$instance, '_empty'];
            $vars = [$action];
        } else {
            // 操作不存在
            throw new \Exception('method not exists: ' . get_class($instance) . '->' . $action . '()');
        }

        // 请求参数校验
        $this->request->check($class . '@' . $action);

        $data = call_user_func_array($call, $vars);

        // TODO 返回参数校验
        // $this->api->make('response')->check($class . '@' . $action);

        return $data;
    }

    /**
     * get dispatch
     *
     * @param $dispatch
     *
     * @return mixed
     * @throws \Exception
     */
    public function getDispatch($dispatch)
    {
        if (array_key_exists($dispatch, $this->rules)) {
            return $this->rules[$dispatch];
        } else {
            // 操作不存在
            throw new \Exception('route not found');
        }
    }

    /**
     * register
     *
     * @return $this
     */
    public function register()
    {
        $routes = $this->api->make('config')->get('route');

        foreach ($routes as $name => $route) {

            if ($route instanceof Closure) {
                $this->rules[$name] = $route;
            } elseif (is_string($route)) {
                $this->parseRule($name, $route);
            } else {
                throw new \Exception(sprintf('route invalid: %s.', $route));
            }
        }

        return $this;
    }

    /**
     * parseRule
     *
     * @param        $name
     * @param        $rule
     * @param string $type
     */
    public function parseRule($name, $rule)
    {
        $routeArr = explode('@', $rule);
        if (count($routeArr) === 1) {
            $class  = $routeArr[0];
            $action = 'index';
        } elseif (count($routeArr) === 2) {
            $class  = $routeArr[0];
            $action = $routeArr[1];
        } else {
            throw new \Exception(sprintf('route invalid: %s.', $rule));
        }

        $route = [$class, $action];

        $this->rules[$name] = $route;
    }
}