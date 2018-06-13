<?php

/**
 *
 * 1. For API
 * /?action=create&user_id=1&time=140232432&token=sdf13245sdaf3sd51cv56g3a
 *
 * 2. For Web
 * /server/restart?id=123012
 */
return [
    '' => function() {
        return 'welcome.';
    },
    'server_create'  => 'app\index\controller\server\Create@index',
    'server_restart' => 'app\index\controller\server\Restart@index',
];