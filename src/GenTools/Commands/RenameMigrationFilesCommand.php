<?php

namespace LaravelCommonNew\GenTools\Commands;

use Carbon\Carbon;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class RenameMigrationFilesCommand extends BaseCommand
{
    protected $name = 'RenameMigrationFiles';
    protected $description = 'Command description';

    /**
     * @return array[]
     */
    protected function getArguments(): array
    {
        return [
            ['day', InputArgument::OPTIONAL, 'day'],
        ];
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $migrationPath = database_path('migrations/');
        $files = File::allFiles($migrationPath);
        $day = $this->argument('day') ? Carbon::parse($this->argument('day')) : now();
        foreach ($files as $file) {
            $oldFileName = $file->getFilename();
            if ($this->inBlackList($oldFileName))
                continue;
            $newFileName = $this->getNewFilename($oldFileName, $day);
            File::move($migrationPath . $oldFileName, $migrationPath . $newFileName);
            $this->line("$oldFileName ==> $newFileName");
        }
    }

    /**
     * @param string $oldFileName
     * @param Carbon $day
     * @return string
     */
    private function getNewFilename(string $oldFileName, Carbon $day): string
    {
        $arr = explode('_', $oldFileName);
        $arr[0] = $day->year;
        $arr[1] = $day->month;
        $arr[2] = $day->day;
        $arr[3] = '000000';
        return implode('_', $arr);
    }

    /**
     * @param string $oldFileName
     * @return bool
     */
    private function inBlackList(string $oldFileName): bool
    {
        $blacklists = ['create_failed_jobs_table', 'create_personal_access_tokens_table'];
        foreach ($blacklists as $list) {
            if (Str::contains($oldFileName, $list))
                return true;
        }
        return false;
    }
}
