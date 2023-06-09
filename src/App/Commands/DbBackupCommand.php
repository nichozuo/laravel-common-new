<?php

namespace LaravelCommonNew\App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DbBackupCommand extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'iseed backup command';

    /**
     * @return void
     */
    public function handle(): void
    {
        $list = config('common.iSeedBackupList', []);
        foreach ($list as $item) {
            $this->line("backup:::$item");
            Artisan::call("iseed $item --force");
        }
    }
}
