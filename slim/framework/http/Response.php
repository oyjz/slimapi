<?php
// +----------------------------------------------------------------------
// | slimAPI [ A slim framework for API ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://www.slimapi.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Jin.oy <503ouyang@sina.com>
// +----------------------------------------------------------------------

namespace slim\http;

use slim\Api;

class Response
{

    /**
     * The api instance.
     *
     * @var \slim\Api
     */
    private $api;

    protected $data;

    protected $header;

    protected $statusCode = '200';

    protected $config;

    protected $returnType = 'json';

    protected $contentType = 'application/json';

    protected $contentTypeAllow
        = [
            'json' => 'application/json',
            'xml'  => 'text/xml',
        ];

    const DEFAULT_ENCODING_OPTIONS = 15;

    protected $encodingOptions = self::DEFAULT_ENCODING_OPTIONS;

    /**
     * Create a new class instance.
     *
     * @param  \slim\Api|null $api
     *
     * @return void
     */
    public function __construct(Api $api = null, array $data = [])
    {
        $this->api     = $api;
        $this->data    = $data;
        $this->request = $api->make('request');
        $this->config  = $this->api->make('config')->get('api');
    }

    /**
     * send
     *
     * @return mixed
     */
    public function send($call = null)
    {

        // do something
        $this->setReturnType()->contentType($this->contentType)->expires(-1);

        if (!headers_sent() && !empty($this->header)) {
            // 发送状态码
            http_response_code($this->statusCode);
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                if (is_null($val)) {
                    header($name);
                } else {
                    header($name . ':' . $val);
                }
            }
        }

        if (isset($call)) {
            // 支持自定义方法处理返回结果
        }

        return call_user_func_array([$this, $this->returnType], []);
    }

    /**
     * setReturnType
     *
     * @return $this
     */
    private function setReturnType()
    {
        $returnType = $this->config['return_type'];

        if (preg_match('/^(json|xml)$/', $returnType)) {
            $this->contentType = $this->contentTypeAllow[$returnType];
            $this->returnType  = $returnType;
        }

        return $this;
    }

    /**
     * json
     *
     * @throws \Exception
     */
    public function json()
    {
        try {
            $data = $this->data;

            if (is_array($data)) {
                $data = array_merge($this->config['return_tpl'], $data);
                $data = json_encode($data, $this->encodingOptions);
            } else {
                throw new \InvalidArgumentException('response data is invalid.');
            }

            if ($data === false) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }

            echo $data;

        } catch (\Exception $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }

    /**
     * xml
     *
     * @throws \Exception
     */
    public function xml()
    {
        try {
            $data = $this->data;

            if (is_array($data)) {
                $data = $this->arrayToXml($data);
            } else {
                throw new \InvalidArgumentException('response data is invalid.');
            }

            echo $data;

        } catch (\Exception $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }

    /**
     * success
     *
     * @param array $message
     */
    public function success($message = [])
    {
        $responseConfig = $this->config['response'];
        $code           = $responseConfig['success_value'];
        $_code          = $this->api->make('code');

        // 根据 success_type get http status code
        if ($responseConfig['success_type'] == 'status') {
            $this->statusCode = $_code->getStatus($code);
        } else {
            $message[$responseConfig['code_key']] = $code;
        }

        $data = $this->api->make('doc')->success();
        foreach ($data as $key => $val) {
            $this->data[$key] = isset($message[$key]) ? $message[$key] : $val['default'];
        }
        $this->data = array_merge($message, $this->data);
        $this->send();
    }

    /**
     * error
     *
     * @param       $code
     * @param array ...$message
     */
    public function error($code, ...$message)
    {
        $_code          = $this->api->make('code');
        $message        = $_code->getMessage($code, ...$message);
        $responseConfig = $this->config['response'];

        $_data = [];
        // 根据 success_type get http status code
        if ($responseConfig['success_type'] == 'status') {
            $this->statusCode = $_code->getStatus($code);
        } else {
            $_data[$responseConfig['code_key']] = $code;
        }
        $_data[$responseConfig['message_key']] = $message;

        $data = $this->api->make('doc')->error();
        foreach ($data as $key => $val) {
            $this->data[$key] = isset($_data[$key]) ? $_data[$key] : $val['default'];
        }
        $this->send();
    }

    /**
     * arrayToXml
     *
     * @param     $arr
     * @param int $dom
     * @param int $item
     *
     * @return string
     */
    private function arrayToXml($arr, $dom = 0, $item = 0)
    {
        if (!$dom) {
            $dom = new \DOMDocument("1.0");
        }
        if (!$item) {
            $item = $dom->createElement("root");
            $dom->appendChild($item);
        }
        foreach ($arr as $key => $val) {
            $itemx = $dom->createElement(is_string($key) ? $key : "item");
            $item->appendChild($itemx);
            if (!is_array($val)) {
                $text = $dom->createTextNode($val);
                $itemx->appendChild($text);

            } else {
                $this->arrayToXml($val, $dom, $itemx);
            }
        }

        return $dom->saveXML();
    }

    /**
     * code
     *
     * @param $code
     *
     * @return $this
     */
    public function status($status)
    {
        $this->statusCode = $status;

        return $this;
    }

    /**
     * contentType
     *
     * @param $contentType
     *
     * @return $this
     */
    public function contentType($contentType)
    {
        $this->header['Content-Type'] = $contentType . '; charset=utf-8';

        return $this;
    }

    /**
     * expires
     *
     * @param $time
     *
     * @return $this
     */
    public function expires($time)
    {
        $this->header['Expires'] = $time;

        return $this;
    }

    /**
     * apiName
     *
     * @param $name
     *
     * @return $this
     */
    public function apiName($name)
    {
        $this->header['X-Api-Name'] = $name;

        return $this;
    }

    /**
     * powered
     *
     * @param $name
     *
     * @return $this
     */
    public function powered($name)
    {
        $this->header['X-Powered-By'] = $name;

        return $this;
    }

    /**
     * code
     *
     * @param $code
     *
     * @return $this
     */
    public function getStatus()
    {
        return $this->statusCode;
    }
}