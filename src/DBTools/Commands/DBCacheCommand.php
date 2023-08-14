<?php

namespace LaravelCommonNew\DBTools\Commands;

use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;
use LaravelCommonNew\DBTools\DBToolsServices;

class DBCacheCommand extends Command
{
    protected $signature = 'dbc';
    protected $description = 'Cache DBModel to disk';

    /**
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        DBToolsServices::CacheAll();
        $this->line('db cached...');
        return 0;
    }
}