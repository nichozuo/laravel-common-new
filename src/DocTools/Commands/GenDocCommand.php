<?php

namespace LaravelCommonNew\DocTools\Commands;

use cebe\openapi\exceptions\TypeErrorException;
use Illuminate\Console\Command;
use LaravelCommonNew\DocTools\DocToolsServices;

class GenDocCommand extends Command
{
    protected $signature = 'gd';
    protected $description = 'gen openapi v3 doc';

    /**
     * @return int
     * @throws TypeErrorException
     */
    public function handle(): int
    {
        DocToolsServices::GenDoc();
        return 0;
    }
}
