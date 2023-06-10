<?php

namespace LaravelCommonNew\App\Commands;

use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;
use LaravelCommonNew\DBTools\DBToolsServices;

class DDCommand extends Command
{
    protected $signature = 'dd {table}';
    protected $description = 'dump the fields of the table';

    /**
     * @return void
     */
    public function handle(): void
    {
        $tableName = $this->argument('table');

        $table = DBToolsServices::Remember()->tables[$tableName];
        if (!$table) {
            $this->error("table $tableName not found");
            return;
        }

        $this->warn('Gen Table template');
        $this->line("protected \$table = '$table->name';");
        $this->line("protected string \$comment = '$table->comment';");
        $this->line("protected \$fillable = [$table->fillableString];");

        $this->warn('gen Validate template');
        $this->line(implode(PHP_EOL, $table->validateString));

        $this->warn('gen Insert template');
        $this->line(implode(PHP_EOL, $table->insertString));
    }
}
