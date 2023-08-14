<?php

namespace LaravelCommonNew\GenTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LaravelCommonNew\DBTools\EnumToolsServices;
use ReflectionException;

class GenAllEnumsCommand extends Command
{
    protected $signature = 'gae';
    protected $description = 'Gen enums to ts file';

    /**
     * @return void
     * @throws ReflectionException
     */
    public function handle(): void
    {
        $str = EnumToolsServices::ToString();
        File::put(storage_path('enums.ts'), $str);
    }
}
