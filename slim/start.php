<?php
/**
 * SLIM API START
 *
 * Before you start, you may be need to know:
 * 1. He is only for API, and he is not 灵活.
 * 2. He just support get and post request.
 * 3. He just support json and xml response, debug 除外.
 * 4. He just support nagtive 原生sql.
 * 5. He just support one enter, no path url.
 *
 * above those bad things, there are some good I also want you know:
 * 1. He can fast and 高效地 for development.
 * 2. He can 减少 misstake.
 * 3. He can makesure you API safety do his best.
 * 4. Wating you found.
 */

define('START_TIME', microtime(true));
define('START_MEM', memory_get_usage());
define('DS', DIRECTORY_SEPARATOR);
define('EXT', '.php');
defined('SLIM_PATH') or define('SLIM_PATH', __DIR__ . DS);
defined('PUBLIC_PATH') or define('PUBLIC_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(PUBLIC_PATH)) . DS);
defined('APP_PATH') or define('APP_PATH', ROOT_PATH . 'app' . DS);
defined('VENDOR_PATH') or define('VENDOR_PATH', ROOT_PATH . 'vendor' . DS);
defined('LOG_PATH') or define('LOG_PATH', ROOT_PATH . 'log' . DS);
defined('CONFIG_PATH') or define('CONFIG_PATH', ROOT_PATH . 'config' . DS);
defined('VIEW_PATH') or define('VIEW_PATH', ROOT_PATH . 'view' . DS);
defined('CACHE_PATH') or define('CACHE_PATH', ROOT_PATH . 'cache' . DS);

/*
 * Autoloder include
 */
require SLIM_PATH . 'framework/autoloder/Autoloder.php';

// Autoloder register
\slim\autoloder\Autoloder::instance()->register();

// API Run
$api = new slim\Api();
$api->run($api->make('request'))->send();