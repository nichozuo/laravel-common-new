<?php

namespace LaravelCommonNew\App\Exceptions;

class ErrConst
{
    const UserNotLoggedIn = ['message' => '用户未登录', 'code' => 10000];
    const AccountPasswordError = ['message' => '账号密码错误', 'code' => 10001];

    const PerPageIsNotAllow = ['message' => '每页记录数不在允许的范围内', 'code' => 10011];
}