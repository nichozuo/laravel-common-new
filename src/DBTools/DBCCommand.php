<?php

namespace LaravelCommonNew\DBTools;

use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;

class DBCCommand extends Command
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