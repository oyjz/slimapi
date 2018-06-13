<?php

namespace slim\bootstrap;

use slim\Api;
use slim\lang\Lang;
use slim\support\File;
use slim\config\Config;

class LoadConfig
{

    protected $config;

    protected $files;

    /**
     * bootstrap
     *
     * @param Api $api
     */
    public function bootstrap(Api $api)
    {
        /*
         * TODO 如果存在缓存，则直接加载缓存配置
        if($isCached){

        }
        */

        $this->getConfigFiles();

        $this->loadConfig();

        $api->instance('config', $config = new Config($api, $this->config));

        $dispathch = $config->get('api.dispatch_type') === 'api' ? 'slim\\route\\DispatchApi' : 'slim\\route\\DispatchWeb';

        $api->bind('dispatch', function($api) use ($dispathch) {
            return new $dispathch($api);
        });

        date_default_timezone_set($config->get('api.timezone', 'Asia/Shanghai'));

        if (!empty($config->get('api.lang'))) {
            $api->make('lang')->range($config->get('api.lang'));
        }

        mb_internal_encoding('UTF-8');
    }

    /**
     * load all config files
     *
     * @return array
     */
    protected function loadConfig()
    {
        $handler = function($files) use (&$handler) {
            $config = [];
            foreach ($files as $key => $path) {
                if (is_array($path)) {
                    $config[$key] = $handler($path);
                } elseif (is_file($path)) {
                    $config[$key] = require $path;
                }
            }

            return $config;
        };

        return $this->config = $handler($this->files);
    }

    /**
     * getConfigFiles
     *
     * @return array
     */
    protected function getConfigFiles()
    {
        $files = File::getPathList(CONFIG_PATH);

        return $this->handleConfigFiles($files);
    }

    /**
     * getConfigFiles
     *
     * @param $files
     *
     * @return array
     */
    protected function handleConfigFiles($files)
    {
        $handler = function($list) use (&$handler) {
            $files = [];
            foreach ($list as $key => $val) {
                if (is_array($val)) {
                    $files[$key] = $handler($val);
                } else {
                    $files[basename($val, EXT)] = $val;
                }
            }

            return $files;
        };

        return $this->files = $handler($files);
    }
}