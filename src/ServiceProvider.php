<?php

namespace LaravelCommonNew;

use Illuminate\Database\Schema\Blueprint;
use LaravelCommonNew\App\Console\Commands\DbBackupCommand;
use LaravelCommonNew\App\Console\Commands\DbCacheCommand;
use LaravelCommonNew\App\Console\Commands\DumpTableCommand;
use LaravelCommonNew\App\Console\Commands\GenEnumsToJSCommand;
use LaravelCommonNew\App\Console\Commands\GenFilesCommand;
use LaravelCommonNew\App\Console\Commands\RenameMigrationFilesCommand;
use LaravelCommonNew\App\Console\Commands\UpdateModelsCommand;
use LaravelCommonNew\App\Helpers\DBHelper;

/**
 * @method addColumn(string $string, string $column, array $compact)
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // commands
        $this->commands([
            DbBackupCommand::class,
            DbCacheCommand::class,
            DumpTableCommand::class,
            GenEnumsToJSCommand::class,
            GenFilesCommand::class,
            RenameMigrationFilesCommand::class,
            UpdateModelsCommand::class
        ]);

        // blueprint macros
        Blueprint::macro('xEnum', function (string $column, mixed $enumClass, string $comment) {
            $length = $enumClass::GetMaxLength();
            $allowed = $enumClass::columns();
            return $this->addColumn('enum', $column, compact('length', 'allowed'))->comment($enumClass::comment($comment));
        });

        Blueprint::macro('xPercent', function (string $column, $total = 5, $places = 2, $unsigned = false) {
            return $this->addColumn('float', $column, compact('total', 'places', 'unsigned'));
        });

        Blueprint::macro('xMoney', function (string $column, $total = 10, $places = 2, $unsigned = false) {
            return $this->addColumn('float', $column, compact('total', 'places', 'unsigned'));
        });
    }

    public function boot()
    {
        DBHelper::Schema();

        $this->publishes([
            __DIR__ . '/resources/dist' => public_path('docs'),
            __DIR__ . '/config/common.php' => config_path("common.php"),
        ]);

        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
    }
}
