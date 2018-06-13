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

namespace slim\autoloder;

/**
 * Class Autoloder
 * @package slim
 */
class Autoloder
{
    /**
     * instance
     *
     * @var
     */
    public static $instance;

    /**
     * autoloder
     *
     * @var
     */
    protected static $autoloder;

    /**
     * Autoloder config
     *
     * @var array
     */
    protected static $config
        = [
            'namespaceMaps' => [
                'slim' => SLIM_PATH . 'framework' . DS,
                'app'  => APP_PATH,
            ],
        ];


    /**
     * instance
     * @return static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public static function config($config = null)
    {
        if (!empty($config)) {
            static::$config['namespaceMaps'] = array_merge($config['namespaceMaps'], static::$config['namespaceMaps']);
            //static::$config['classAlias'] = array_merge($config['classAlias'], static::$config['classAlias']);
            //static::$config['namespaceAlias'] = array_merge($config['namespaceAlias'], static::$config['namespaceAlias']);
        }
    }


    /**
     * autoload
     *
     * @param $class
     *
     * @return bool
     */
    public function autoload($class)
    {
        $this->handleNamespaceAlias($class);

        if ($file = $this->findFile($class)) {
            // Win环境严格区分大小写
            if (strpos(PHP_OS, 'WIN') !== false && pathinfo($file, PATHINFO_FILENAME) != pathinfo(realpath($file), PATHINFO_FILENAME)) {
                return false;
            }

            __include_file($file);

            return true;
        }
    }

    /**
     * find file
     *
     * @param $class
     *
     * @return mixed|string
     */
    private function findFile($class)
    {

        if ($file = $this->handleNamespaceMaps($class)) {
            return $file;
        }

        if ($file = $this->handleClassAlias($class)) {
            return $file;
        }

        $logicalPathPsr4 = strtr($class, '\\', DS) . EXT;

        return ROOT_PATH . $logicalPathPsr4;
    }

    /**
     * handleNamespaceAlias
     *
     * @param $class
     *
     * @return mixed
     */
    private function handleNamespaceAlias($class)
    {
        $namespace = dirname($class);
        if (!empty(static::$config['namespaceAlias'][$namespace])) {
            $original = static::$config['namespaceAlias'][$namespace] . '\\' . basename($class);
            if (class_exists($original)) {
                return class_alias($original, $class, false);
            }
        }
    }

    /**
     * handleNamespaceMaps
     *
     * @param $class
     *
     * @return bool|string
     */
    private function handleNamespaceMaps($class)
    {
        $classArr  = explode('\\', $class);
        $namespace = $classArr[0];

        if (!empty(static::$config['namespaceMaps'][$namespace])) {
            array_shift($classArr);
            $class = implode(DS, $classArr);
            $file  = static::$config['namespaceMaps'][$namespace] . $class . EXT;

            return $file;
        }

        return false;
    }

    /**
     * handleClassAlias
     *
     * @param $class
     *
     * @return bool
     */
    private function handleClassAlias($class)
    {
        if (!empty(static::$config['classAlias'][$class])) {
            return static::$config['classAlias'][$class];
        }

        return false;
    }


    /**
     * autoload register
     *
     * @param string $autoload
     */
    public function register($autoload = '')
    {
        if (!self::$autoloder) {
            self::$autoloder = true;

            // autoload_register
            spl_autoload_register($autoload ?: array($this, 'autoload'), true, true);

            // composer
            if (is_dir(VENDOR_PATH . 'composer')) {
                __require_file(VENDOR_PATH . 'autoload.php');
            }
        }
    }

}

/**
 * 作用范围隔离
 *
 * @param $file
 *
 * @return mixed
 */
function __include_file($file)
{
    return include $file;
}

function __require_file($file)
{
    return require $file;
}
