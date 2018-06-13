<?php

// TODO regular 预设
return [
    'method'  => 'get',
    'params'  => [
        'instance_id' => [
            'type'        => 'int',
            'regular'     => '/^[1-9][0-9]{0,9}$/',
            'demo'        => 1,
            'default'     => 0,
            'description' => '实例规格ID',
        ],
        'bandwidth'   => [
            'type'        => 'int',
            'demo'        => 1,
            'default'     => 0,
            'description' => '购买带宽大小，范围：1-1000',
            'regular'     => '/^([1-9][0-9]{0,2}|1000)$/',
        ],
        'defense'     => [
            'type'        => 'int',
            'regular'     => '/^(5|10|20|100|200|300)$/',
            'demo'        => 5,
            'default'     => 0,
            'description' => '购买防御大小，范围：5-300',
        ],
        'months'      => [
            'type'        => 'int',
            'regular'     => '/^(1|2|3|4|5|6|7|8|9|12|24|36)$/',
            'demo'        => 1,
            'default'     => 0,
            'description' => '购买时长，范围：1-36',
        ],
        'remarks'     => [
            'type'        => 'string',
            'regular'     => '/^([\s\S]{0,200}|\s*)$/',
            'demo'        => '购买1H1G时长1个月',
            'default'     => '',
            'description' => '备注信息',
        ],
    ],
    'success' => [
        'server_id' => [
            'type'        => 'int',
            'demo'        => '1206354',
            'default'     => 0,
            'description' => '实例ID',
            'regular'     => '/^[1-9][0-9]{0,9}$/',
        ],
    ],
];