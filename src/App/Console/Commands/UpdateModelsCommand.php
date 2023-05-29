<?php

namespace LaravelCommonNew\App\Console\Commands;

use LaravelCommonNew\App\Helpers\TableHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdateModelsCommand extends Command
{
    protected $signature = 'update:models';
    protected $description = 'Command description';

    /**
     * @return void
     */
    public function handle(): void
    {
        foreach (TableHelper::GetTables() as $table) {
            $name = $table->getName();
            $this->line($name . ':::');
            Artisan::call("gf $name -d -f");
        }
    }
}
