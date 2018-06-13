<?php

return [
    'method'  => 'get',
    'params'  => [
        'secrute_id' => [
            'type'        => 'string',
            'demo'        => '654321646123afdsdf16',
            'default'     => '',
            'description' => '安全ID，请像服务商索取',
        ],
        'time'       => [
            'type'        => 'timestamp',
            'demo'        => '1528555850',
            'default'     => '',
            'description' => '当前请求时间戳',
        ],
        'token'      => [
            'type'        => 'string',
            'demo'        => 'cd9d15f3950ff9c94ad6423f262267af',
            'default'     => '',
            'description' => 'token，根据指定加密规则生成',
        ],
    ],
    'success' => [
        'request_id' => [
            'type'        => 'string',
            'demo'        => '1528555850',
            'default'     => '',
            'description' => '当前请求ID，防止重复请求导致异常',
        ],
    ],
    'error'  => [
        'request_id' => [
            'type'        => 'string',
            'demo'        => '4C467B38-3910-447D-87BC-AC049166F216',
            'default'     => '',
            'description' => '当前请求ID，防止重复请求导致异常',
        ],
        'code'    => [
            'type'        => 'string',
            'demo'        => 'error',
            'default'     => '',
            'description' => '返回错误码',
        ],
        'message'    => [
            'type'        => 'string',
            'demo'        => 'success',
            'default'     => 'success',
            'description' => '返回提示信息',
        ],
    ],
];