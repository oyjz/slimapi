<?php

/*
class loader
{
    public function autoload($type = 1)
    {

        echo 'autoload start: ' . $type . '<hr>';

        if ($type === 1) {
            __include_file(__DIR__ . '/test.php');
        } elseif ($type === 2) {
            include __DIR__ . '/test.php';
        }

        if (isset($hello)) {
            echo $hello;
        }

        echo '<hr> autoload end: ' . $type;

    }
}

function __include_file($file)
{
    return include $file;
}


$loader = new loader();

//$loader->autoload(1);

$loader->autoload(2);

exit;*/

// 加载框架引导文件
require __DIR__ . '/../slim/start.php';

//$container = new slim\container\Container();


//$container->call();
//var_dump($container->bound('testaf df'));


//$test = new \app\Test();
//$test->test();


//$config = new \slim\bootstrap\Config();
//var_dump($config->getConfigFiles11());
/*
$serviceProviders = [
    \app\provider\Config::class,
    \app\provider\Database::class
];

array_walk($serviceProviders, function ($p) {
    bootProvider($p);
});


function bootProvider($provider)
{
    if (method_exists($provider, 'boot')) {
        return call_user_func_array([$provider, 'boot'],[]);
    }
}*/
