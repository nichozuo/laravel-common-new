<?php

return [
    'perPageAllow' => [10, 20, 50, 100],
    'dontWrapJson' => [
        '/api/customer/notify/pay',
    ],
    'iSeedBackupList' => [
        'sys_permissions',
        'sys_roles',
        'sys_role_has_permissions',
        'sys_model_has_roles',
        'personal_access_tokens',
    ],
];
