<?php

namespace LaravelCommonNew\App\Exceptions;

use Exception;

class Err extends Exception
{
    const UserNotLogin = ['message' => '用户未登录', 'code' => 10000];

    /**
     * @param string $message
     * @param int $code
     * @return mixed
     * @throws Err
     */
    public static function Throw(string $message, int $code = 9999): mixed
    {
        throw new static($message, $code);
    }
}
