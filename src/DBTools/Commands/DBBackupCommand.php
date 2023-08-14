<?php

namespace LaravelCommonNew\DBTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DBBackupCommand extends Command
{
    protected $signature = 'dbb';
    protected $description = 'iseed backup command';

    /**
     * @return int
     */
    public function handle(): int
    {
        $list = config('common.dbBackupList', []);
        foreach ($list as $item) {
            $this->line("backup:::$item");
            Artisan::call("iseed $item --force");
        }
        return 0;
    }
}
