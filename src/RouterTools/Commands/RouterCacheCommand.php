<?php

namespace LaravelCommonNew\RouterTools\Commands;

use Illuminate\Console\Command;
use LaravelCommonNew\RouterTools\RouterToolsServices;
use ReflectionException;

class RouterCacheCommand extends Command
{
    protected $signature = 'rtc';
    protected $description = 'Cache DBModel to disk';

    /**
     * @return int
     */
    public function handle(): int
    {
        RouterToolsServices::AutoGenRouters(null);
        $this->line('router cached...');
        return 0;
    }
}