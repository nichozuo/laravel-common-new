<?php

namespace LaravelCommonNew\App\Helpers;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Illuminate\Support\Facades\DB;

class DbalHelper
{
    private static ?AbstractSchemaManager $DB = null;

    /**
     * @param string $config
     * @return AbstractSchemaManager
     */
    public static function SM(string $config = ''): AbstractSchemaManager
    {
        if (null == self::$DB) {
            self::$DB = DB::connection($config)->getDoctrineSchemaManager();
        }
        return self::$DB;
    }

    /**
     * @return void
     * @throws Exception
     */
    public static function register(): void
    {
        self::SM()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
}
