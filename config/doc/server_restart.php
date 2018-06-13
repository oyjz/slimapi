<?php

return [
    'method'     => 'get',
    'params'     => [
        'instance_id' => [
            'type'        => 'int',
            'demo'        => 1,
            'default'     => 0,
            'description' => '实例规格ID',
        ],
        'bandwidth'   => [
            'type'        => 'int',
            'demo'        => 1,
            'default'     => 0,
            'description' => '购买带宽大小，范围：1-1000',
        ],
        'defense'     => [
            'type'        => 'int',
            'demo'        => 5,
            'default'     => 0,
            'description' => '购买防御大小，范围：5-300',
        ],
        'moenths'     => [
            'type'        => 'int',
            'demo'        => 1,
            'default'     => 0,
            'description' => '购买时长，范围：1-36',
        ],
        'remarks'     => [
            'type'        => 'string',
            'demo'        => '购买1H1G时长1个月',
            'default'     => '',
            'description' => '备注信息',
        ],
    ],
    'success'    => [
        'server_id' => [
            'type'        => 'int',
            'default'     => 0,
            'description' => '实例规格ID',
        ],
    ],
];