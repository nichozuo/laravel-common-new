<?php

namespace LaravelCommonNew\DBTools;

use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;

class DBCacheCommand extends Command
{
    protected $signature = 'dbc';
    protected $description = 'Cache DBModel to disk';

    /**
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        DBToolsServices::CacheIt();
        $this->line('db cached...');
    }
}