<?php

namespace LaravelCommonNew\GenTools;

use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GCommand extends Command
{
    protected $name = 'g';
    protected $description = 'Generate files';

    /**
     * @return array[]
     */
    protected function getArguments(): array
    {
        return [
            ['key', InputArgument::REQUIRED, 'table name'],
        ];
    }

    /**
     * @return array[]
     */
    protected function getOptions(): array
    {
        return [
            ['migration', 'm', InputOption::VALUE_NONE, 'gen migration file'],
            ['model', 'd', InputOption::VALUE_NONE, 'The name of the model'],
            ['controller', 'c', InputOption::VALUE_NONE, 'gen controller file'],
            ['test', 't', InputOption::VALUE_NONE, 'gen test file'],
            ['enum', 'e', InputOption::VALUE_NONE, 'gen enum file'],
            ['force', 'f', InputOption::VALUE_NONE, 'force overwrite'],
        ];
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $options = $this->options();
        $key = $this->argument('key');
        $force = $options['force'];

        if ($options['migration']) {
            $tableName = Str::of($key)->snake()->plural();
            $this->call('make:migration', [
                'name' => "create_{$tableName}_table",
                '--create' => $tableName,
                '--table' => $tableName,
            ]);
        } elseif ($options['model']) {
            $tableName = Str::of($key)->snake()->plural();
            GenFilesServices::GenModels($tableName, $force);
        } elseif ($options['controller']) {
            $arr = explode('/', $key);
            $tableName = Str::of(last($arr))->snake()->plural();
            array_pop($arr);
            $arr = array_map(function ($value) {
                return Str::of($value)->studly();
            }, $arr);
            GenFilesServices::GenController($arr, $tableName, $force);
        } elseif ($options['test']) {

        } elseif ($options['enum']) {
            $this->makeEnum($key);
            return;
        } elseif ($options['force']) {

        } else {

        }
    }
}