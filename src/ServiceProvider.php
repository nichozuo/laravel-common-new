<?php

namespace LaravelCommonNew;

use Illuminate\Database\Schema\Blueprint;
use LaravelCommonNew\DBTools\Commands\DBBackupCommand;
use LaravelCommonNew\DBTools\Commands\DBCacheCommand;
use LaravelCommonNew\DBTools\Commands\DBDumpCommand;
use LaravelCommonNew\DocTools\Commands\GenDocCommand;
use LaravelCommonNew\GenTools\Commands\GenAllEnumsCommand;
use LaravelCommonNew\GenTools\Commands\GenAllModelsCommand;
use LaravelCommonNew\GenTools\Commands\GenFilesCommand;
use LaravelCommonNew\RouterTools\Commands\RouterCacheCommand;


/**
 * @method addColumn(string $string, string $column, array $compact)
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        // commands
        $this->commands([
            DBCacheCommand::class,
            DBBackupCommand::class,
            DBDumpCommand::class,
            RouterCacheCommand::class,
            GenFilesCommand::class,
            GenAllEnumsCommand::class,
            GenAllModelsCommand::class,
            GenDocCommand::class,
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
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/App/common.php' => config_path("common.php"),
        ]);
    }
}
