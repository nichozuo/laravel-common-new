<?php

namespace LaravelCommonNew\App\Console\Commands;

use LaravelCommonNew\App\Helpers\TableHelper;
use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;

class DbCacheCommand extends Command
{
    protected $signature = 'db:cache';
    protected $description = 'clean the db cache files';

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        TableHelper::ReCache();
        $this->line('db cached...');
    }

}
