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
        Blueprint::macro('amount', function (?string $fieldName = 'amount', string $comment = '', int $default = 0, bool $nullable = false) {
            $this->decimal($fieldName, 30, 6)->comment($comment)->default($default)->nullable($nullable);
        });
        Blueprint::macro('address', function (?string $fieldName = 'address', string $comment = '', string $default = null, bool $nullable = false) {
            $this->string($fieldName, 92)->comment($comment)->default($default)->nullable($nullable);
        });
        Blueprint::macro('float8', function (string $fieldName, string $comment = '', int $default = 0, bool $nullable = false) {
            $this->double($fieldName, 20, 6)->comment($comment)->default($default)->nullable($nullable);
        });
        Blueprint::macro('myEnum', function (string $fieldName, mixed $enum, string $comment = '', string $default = null, bool $nullable = false) {
            $this->enum($fieldName, $enum::columns())->comment($enum::comment($comment))->default($default)->nullable($nullable);
        });
    }

    public function boot()
    {
        DBHelper::Schema();

        $this->publishes([
            __DIR__ . '/resources/dist' => public_path('docs'),
            __DIR__ . '/config/common.php' => config_path(),
        ]);

        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
    }
}
