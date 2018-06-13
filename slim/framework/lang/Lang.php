<?php

namespace slim\lang;

use slim\Api;

class Lang
{

    protected $api;

    private $lang = [];

    private $range = 'zh';

    /**
     * Lang constructor.
     *
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api  = $api;
        $this->lang = $this->api->make('config')->get('lang');
    }

    /**
     * range
     *
     * @param string $range
     *
     * @return string
     */
    public function range($range = '')
    {
        if ('' == $range) {
            return $this->range;
        } else {
            if(isset($this->lang[$range])){
                $this->range = $range;
            }
        }

        return $this->range;
    }

    /**
     * check the key
     *
     * @param        $name
     * @param string $range
     *
     * @return bool
     */
    public function has($name, $range = '')
    {
        $range = $range ?: $this->range;

        return isset($this->lang[$range][strtolower($name)]);
    }

    /**
     * get key
     *
     * @param null   $name
     * @param string $range
     *
     * @return mixed|null
     */
    public function get($name = null, $range = '')
    {
        $range = $range ?: $this->range;
        // 空参数返回所有定义
        if (empty($name)) {
            return $this->lang[$range];
        }
        $key   = strtolower($name);
        $value = isset($this->lang[$range][$key]) ? $this->lang[$range][$key] : $name;

        return $value;
    }
}