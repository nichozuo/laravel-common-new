<?php


namespace LaravelCommonNew\App\Traits;


use Exception;

trait ControllerTrait
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
