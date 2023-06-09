<?php

namespace LaravelCommonNew\RouterTools;

use Illuminate\Console\Command;

class RouterCacheCommand extends Command
{
    protected $signature = 'rtc';
    protected $description = 'Cache DBModel to disk';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        RouterToolsServices::Gen();
        $this->line('router cached...');
    }
}