<?php

namespace LaravelCommonNew\App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DBHelper
{
    /**
     * @return void
     */
    public static function LogSql(): void
    {
        if (config('app.debug')) {
            DB::listen(function ($query) {
                $tmp = str_replace('?', '"' . '%s' . '"', $query->sql);
                $qBindings = [];
                if (!empty($query->bindings)) {
                    foreach ($query->bindings as $key => $value) {
                        if (is_numeric($key)) {
                            $qBindings[] = $value;
                        } else {
                            $tmp = str_replace(':' . $key, '"' . $value . '"', $tmp);
                        }
                    }
                    $tmp = vsprintf($tmp, $qBindings);
                    $tmp = str_replace("\\", "", $tmp);
                    Log::info(' execution time: ' . $query->time . 'ms; ' . $tmp . "\n\n\t");
                }
            });
        }
    }

    /**
     * @return void
     */
    public static function Schema(): void
    {
        Schema::defaultStringLength(191);
    }

    /**
     * @param string $tableName
     * @param string $comment
     * @return void
     */
    public static function SetComment(string $tableName, string $comment): void
    {
        DB::statement("ALTER TABLE `$tableName` comment '$comment'");
    }
}
