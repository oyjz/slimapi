<?php

namespace slim\http;

use function PHPSTORM_META\type;
use slim\Api;

class Request
{

    /**
     * The api instance.
     *
     * @var \slim\Api
     */
    private $api;

    /**
     * @var array 请求参数
     */
    protected $param   = [];
    protected $get     = [];
    protected $post    = [];
    protected $request = [];
    protected $route   = [];
    protected $put;
    protected $session = [];
    protected $file    = [];
    protected $cookie  = [];
    protected $server  = [];
    protected $header  = [];

    protected $method;

    /**
     * @var string pathinfo
     */
    protected $pathinfo;

    // php://input
    protected $input;


    /**
     * Request constructor.
     *
     * @param Api   $api
     * @param array $options
     */
    public function __construct(Api $api, array $options = [])
    {
        $this->api = $api;

        foreach ($options as $name => $item) {
            if (property_exists($this, $name)) {
                $this->$name = $item;
            }
        }

        $this->input = file_get_contents('php://input');
    }

    /**
     * get client ip
     *
     * @param int  $type
     * @param bool $adv
     *
     * @return mixed
     */
    public function ip($type = 0, $adv = false)
    {
        $type = $type ? 1 : 0;
        $ip   = null;
        if (null !== $ip) {
            return $ip[$type];
        }

        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim(current($arr));
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? [$ip, $long] : ['0.0.0.0', 0];

        return $ip[$type];
    }


    /**
     * get pathinfo
     *
     * @return string
     */
    public function pathinfo()
    {
        if (is_null($this->pathinfo)) {
            $this->pathinfo = empty($_SERVER['PATH_INFO']) ? '/' : ltrim($_SERVER['PATH_INFO'], '/');
        }

        return $this->pathinfo;
    }


    /**
     * get params
     *
     * @param string $name
     * @param null   $default
     *
     * @return array|mixed
     */
    public function get($name = '', $default = null)
    {
        if (empty($this->get)) {
            $this->get = $_GET;
        }
        if (is_array($name)) {
            $this->param = [];

            return $this->get = array_merge($this->get, $name);
        }

        return $this->input($this->get, $name, $default);
    }

    /**
     * post params
     *
     * @param string $name
     * @param null   $default
     *
     * @return array|mixed
     */
    public function post($name = '', $default = null)
    {
        if (empty($this->post)) {
            $content = $this->input;
            if (empty($_POST) && false !== strpos($this->contentType(), 'application/json')) {
                $this->post = (array)json_decode($content, true);
            } else {
                $this->post = $_POST;
            }
        }
        if (is_array($name)) {
            $this->param = [];

            return $this->post = array_merge($this->post, $name);
        }

        return $this->input($this->post, $name, $default);
    }

    /**
     * get params
     *
     * @param string $name
     * @param null   $default
     *
     * @return array|mixed|null
     */
    public function param($name = '', $default = null)
    {
        if (empty($this->param)) {
            $method = $this->method(true);
            // 自动获取请求变量
            switch ($method) {
                case 'POST':
                    $vars = $this->post(false);
                    break;
                default:
                    $vars = [];
            }
            // 当前请求参数和URL地址中的参数合并
            $this->param = array_merge($this->get(false), $vars);
        }

        return $this->input($this->param, $name, $default);
    }


    /**
     * contentType
     *
     * @return string
     */
    public function contentType()
    {
        $contentType = $this->server('CONTENT_TYPE');
        if ($contentType) {
            if (strpos($contentType, ';')) {
                list($type) = explode(';', $contentType);
            } else {
                $type = $contentType;
            }

            return trim($type);
        }

        return '';
    }

    /**
     * server params
     *
     * @param string $name
     * @param null   $default
     *
     * @return array|mixed
     */
    public function server($name = '', $default = null)
    {
        if (empty($this->server)) {
            $this->server = $_SERVER;
        }
        if (is_array($name)) {
            return $this->server = array_merge($this->server, $name);
        }

        return $this->input($this->server, false === $name ? false : strtoupper($name), $default);
    }


    /**
     * input params
     *
     * @param array  $data
     * @param string $name
     * @param null   $default
     *
     * @return array|mixed|null
     */
    public function input($data = [], $name = '', $default = null)
    {
        if (false === $name) {
            // 获取原始数据
            return $data;
        }
        $name = (string)$name;
        if ('' != $name) {
            // 解析name
            if (strpos($name, '/')) {
                list($name, $type) = explode('/', $name);
            } else {
                $type = 'string';
            }
            // 按.拆分成多维数组进行判断
            foreach (explode('.', $name) as $val) {
                if (isset($data[$val])) {
                    $data = $data[$val];
                } else {
                    // 无输入数据，返回默认值
                    return $default;
                }
            }
            if (is_object($data)) {
                return $data;
            }
        }

        if (isset($type) && $data !== $default) {
            // 强制类型转换
            $this->typeCast($data, $type);
        }

        return $data;
    }

    /**
     * type cast
     *
     * @param $data
     * @param $type
     */
    private function typeCast(&$data, $type)
    {
        switch (strtolower($type)) {
            // 数组
            case 'array':
                $data = (array)$data;
                break;
            // 数字
            case 'int':
                $data = (int)$data;
                break;
            // 浮点
            case 'float':
                $data = (float)$data;
                break;
            // 布尔
            case 'bool':
                $data = (boolean)$data;
                break;
            // 字符串
            case 'string':
            default:
                if (is_scalar($data)) {
                    $data = (string)$data;
                } else {
                    throw new \InvalidArgumentException('variable type error：' . gettype($data));
                }
        }
    }

    /**
     * get method
     *
     * @param bool $method
     *
     * @return mixed|string
     */
    public function method($method = false)
    {
        if (true === $method) {
            // 获取原始请求类型
            return isset($this->server['REQUEST_METHOD']) ? $this->server['REQUEST_METHOD'] : $_SERVER['REQUEST_METHOD'];
        } elseif (!$this->method) {
            if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $this->method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            } else {
                $this->method = isset($this->server['REQUEST_METHOD']) ? $this->server['REQUEST_METHOD'] : $_SERVER['REQUEST_METHOD'];
            }
        }

        return $this->method;
    }


    /**
     * isGet
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->method() == 'GET';
    }

    /**
     * isPost
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->method() == 'POST';
    }


    /**
     * isSsl
     *
     * @return bool
     */
    public function isSsl()
    {
        $server = array_merge($_SERVER, $this->server);
        if (isset($server['HTTPS']) && ('1' == $server['HTTPS'] || 'on' == strtolower($server['HTTPS']))) {
            return true;
        } elseif (isset($server['REQUEST_SCHEME']) && 'https' == $server['REQUEST_SCHEME']) {
            return true;
        } elseif (isset($server['SERVER_PORT']) && ('443' == $server['SERVER_PORT'])) {
            return true;
        } elseif (isset($server['HTTP_X_FORWARDED_PROTO']) && 'https' == $server['HTTP_X_FORWARDED_PROTO']) {
            return true;
        }

        return false;
    }

    public function check($key)
    {
        $config  = $this->api->make('doc')->get($key);
        $method  = strtolower($config['method']);
        $params  = array_change_key_case($config['params']);
        $_method = strtolower($this->method());

        // check method
        if (!preg_match('/^(get|post|\s*)$/', $method)) {
            throw new \InvalidArgumentException('doc method invalid');
        }
        if (!preg_match('/^(get|post)$/', $_method)) {
            throw new \InvalidArgumentException('request method not support');
        }
        if ($method && $method !== $_method) {
            throw new \Exception('request method not support');
        }

        // check params
        if(!$this->checkParams($params)){

        }
    }

    /**
     * checkParams
     *
     * @param $params
     *
     * @return bool
     */
    public function checkParams($params)
    {
        try{
            foreach ($params as $key => $val) {
                $value = $value_origin = $this->param($key);
                $type  = $val['type'] ?: 'string';
                // 1. param type
                if ($value) {
                    $this->typeCast($value, $type);
                }
                // 1. regular check
                if (isset($val['regular']) && !preg_match($val['regular'], $value)) {
                    // TODO 返回参数错误
                    throw new \InvalidArgumentException('request params invalid: ' . $key . '=' . $value_origin);
                }
            }

            return true;
        } catch (\Exception $e){
            return false;
        }
    }
}