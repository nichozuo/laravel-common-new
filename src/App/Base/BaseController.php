<?php

namespace LaravelCommonNew\App\Base;

use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    /**
     * @param array $params
     * @param string $key
     */
    protected function crypto(array &$params, string $key = 'password'): void
    {
        if (isset($params[$key])) {
            if ($params[$key] == '')
                unset($params[$key]);
            else
                $params[$key] = bcrypt($params[$key]);
        }
    }
}