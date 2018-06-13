<?php

namespace slim\doc;

use slim\Api;
use slim\http\Request;

class Doc
{

    protected $api;

    private $common = [];
    private $alias  = [];

    private $docs = [];

    /**
     * Lang constructor.
     *
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
        $this->set();
    }

    public function set()
    {
        // TODO 缓存优化
        $docs         = $this->api->make('config')->get('doc');
        $this->common = $docs['_common'];
        $this->alias  = $docs['_alias'];
        unset($docs['_common']);
        unset($docs['_alias']);
        $_docs = [];
        foreach ($this->alias as $key => $val) {
            if (array_key_exists($key, $docs)) {
                $_docs[$val] = $docs[$key];
            } else {
                throw new \Exception('doc not found: ' . $key);
            }
        }
        $this->docs = $_docs;
    }

    /**
     * check
     *
     * @param string $key
     *
     * @return bool
     */
    public function check($key = '', Request $request)
    {
        if (isset($this->docs[$key])) {
            $rules = $this->docs[$key];
        }

        return true;
    }

    /**
     * get
     *
     * @param string $key
     *
     * @return array|mixed
     */
    public function get($key = '')
    {
        if (!empty($key)) {
            $result = array_replace_recursive($this->common, $this->docs[$key]);
        } else {
            $result = array_map(
                function($doc) {
                    return array_replace_recursive($this->common, $doc);
                }, $this->docs
            );
        }

        return $result;
    }

    /**
     * success
     *
     * @return array|mixed
     */
    public function success()
    {
        return isset($this->common['success']) ? $this->common['success'] : [];
    }

    /**
     * error
     * @return array|mixed
     */
    public function error()
    {
        return isset($this->common['error']) ? $this->common['error'] : [];
    }

    /**
     * TODO make doc file
     *
     * @param string $key
     */
    public function make($key = '')
    {

    }
}