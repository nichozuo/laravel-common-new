<?php

namespace LaravelCommonNew;

use Illuminate\Database\Schema\Blueprint;
use LaravelCommonNew\App\Commands\DbBackupCommand;
use LaravelCommonNew\App\Commands\DTCommand;
use LaravelCommonNew\DBTools\DBCCommand;
use LaravelCommonNew\GenTools\GCommand;
use LaravelCommonNew\RouterTools\RTCCommand;


/**
 * @method addColumn(string $string, string $column, array $compact)
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // commands
        $this->commands([
            DBCCommand::class,
            RTCCommand::class,
            DbBackupCommand::class,
            DTCommand::class,
            GCommand::class,
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

    public function boot()
    {
//        DBHelper::Schema();

//        $this->publishes([
//            __DIR__ . '/resources/dist' => public_path('docs'),
//            __DIR__ . '/config/common.php' => config_path("common.php"),
//        ]);

//        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
    }
}
