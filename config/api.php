<?php

return [
    'name'        => 'slim API',
    'env'         => 'production',
    'debug'       => true,
    'url'         => '',
    'timezone'    => 'Asia/Shanghai',
    'locale'      => 'zh',
    'return_type' => 'json',
    'return_tpl'  => ['code' => 0, 'message' => ''],

    'lang' => 'zh',

    'key'    => '',
    'cipher' => 'AES-256-CBC',

    'dispatch_type' => 'api',  // api|web
    'dispatch_key'  => 'action',  // when dispatch_type is api
    'xml_root_name' => 'slimapi',

    'response' => [
        'success_type'  => 'code',  // status  code
        'success_value' => 'success',
        'code_key'      => 'code',
        'message_key'   => 'message',
    ],

    'log' => [
        'min_level'   => 'debug',
        'apart_level' => ['error'],
        'max_files'   => 30,
        'time_format' => 'Y-m-d H:i:s',
        'path'        => LOG_PATH,
        'cut_type'    => 'month',   /* daily month year  20180101  201801 */
        'file_size'   => 1024 * 1024,   /* if cut_type is size, then setting the file size */
        'ext'         => '.log',
    ],

];