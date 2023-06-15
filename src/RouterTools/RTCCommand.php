<?php

namespace LaravelCommonNew\RouterTools;

use Illuminate\Console\Command;
use ReflectionException;

class RTCCommand extends Command
{
    protected $signature = 'rtc';
    protected $description = 'Cache DBModel to disk';

    /**
     * @return int
     * @throws ReflectionException
     */
    public function handle(): int
    {
        RouterToolsServices::Gen();
        $this->line('router cached...');
        return 0;
    }
}