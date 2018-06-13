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

use slim\support\Str;

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    function windows_os()
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }
}

if (!function_exists('with')) {
    /**
     * Return the given object. Useful for chaining.
     *
     * @param  mixed $object
     *
     * @return mixed
     */
    function with($object)
    {
        return $object;
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        if (strlen($value) > 1 && Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists('ip')) {
    /**
     * Determine if we are running in the console.
     *
     * @return bool
     */
    function ip($type = 0, $adv = false)
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
}


if (!function_exists('convert_size')) {

    /**
     * 单位转换  b自动转换成其他单位
     *
     * @param $size
     *
     * @return string
     */
    function convert_size($size)
    {
        $unit = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

}
