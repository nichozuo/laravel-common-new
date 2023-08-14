<?php

namespace LaravelCommonNew\DBTools\Commands;

use Exception;
use Illuminate\Console\Command;
use LaravelCommonNew\DBTools\DBToolsServices;

class DBDumpCommand extends Command
{
    protected $signature = 'dt {table}';
    protected $description = 'dump the fields of the table';

    /**
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        $tableName = $this->argument('table');

        $table = DBToolsServices::GetTable($tableName);

        $this->warn('Gen Table template');
        $this->line("protected \$table = '$table->name';");
        $this->line("protected string \$comment = '$table->comment';");
        $this->line("protected \$fillable = [$table->fillableString];");

        $this->warn('gen Validate template');
        $this->line(implode(PHP_EOL, $table->validateString));

        $this->warn('gen Insert template');
        $insertString = implode(PHP_EOL, $table->insertString);
        $this->line($insertString);

        return 0;
    }
}
