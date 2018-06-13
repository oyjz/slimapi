<?php

namespace slim\http;

class Code
{
    const SUCCESS            = 'success';
    const ERROR              = 'error';

    /**
     * getResult
     *
     * @param null $code
     *
     * @return array
     */
    public function getResult($code = null, ...$args)
    {
        $message = $this->message();
        if (empty($message[$code])) {
            throw new \InvalidArgumentException('param is empty: Code->getMessage($key)');
        } else {
            $_message = $message[$code]['message'];
            if ($args) {
                $_message = @sprintf($_message, ...$args) ?: $_message;
                // args invalid
                if ($_message == $message[$code]['message']) {
                    // TODO Log
                }
            }
            $_message = str_replace('%s', '', $_message);
            $_message = rtrim(rtrim($_message, ' . '), ' , ');
            $result   = [
                'code'    => $code,
                'message' => $_message,
            ];

            return $result;
        }
    }

    /**
     * getMessage
     *
     * @param null $key
     *
     * @return mixed
     */
    public function getMessage($code = null, ...$args)
    {
        $message = $this->message();
        if (empty($message[$code])) {
            throw new \InvalidArgumentException('param is empty: Code->getMessage($key)');
        } else {
            $_message = isset($message[$code]['message']) ? $message[$code]['message'] : 200;
            if ($args) {
                $_message = @sprintf($_message, ...$args) ?: $_message;
                // args invalid
                if ($_message == $message[$code]['message']) {
                    // TODO Log
                }
            }
            $_message = str_replace('%s', '', $_message);
            $_message = rtrim(rtrim($_message, ' . '), ' , ');

            return $_message;
        }
    }

    /**
     * getCode
     *
     * @param null $key
     *
     * @return int
     */
    public function getStatus($code = null)
    {
        $message = $this->message();
        if (empty($message[$code])) {
            throw new \InvalidArgumentException('param is empty: Code->getMessage($key)');
        } else {
            return isset($message[$code]['status']) ? $message[$code]['status'] : 200;
        }
    }


    protected function message()
    {
        return [
            self::SUCCESS           => [
                'status'  => 200,
                'message' => 'success',
            ],
            self::ERROR             => [
                'status'  => 500,
                'message' => 'error, %s,, ,, .',
            ],
        ];
    }
}