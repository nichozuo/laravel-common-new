<?php

namespace LaravelCommonNew\App\Console\Commands;

use Doctrine\DBAL\Schema\Table;
use Exception;
use LaravelCommonNew\App\Exceptions\Err;
use LaravelCommonNew\App\Helpers\AttrHelper;
use LaravelCommonNew\App\Helpers\DbalHelper;
use LaravelCommonNew\App\Helpers\GenHelper;
use LaravelCommonNew\App\Helpers\ReflectHelper;
use LaravelCommonNew\App\Helpers\StubHelper;
use LaravelCommonNew\App\Helpers\TableHelper;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenFilesCommand extends BaseCommand
{
    protected $name = 'gf';
    protected $description = 'Generate files of the table';

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
            ['model', 'd', InputOption::VALUE_NONE, 'gen model file'],
            ['controller', 'c', InputOption::VALUE_NONE, 'gen controller file'],
            ['test', 't', InputOption::VALUE_NONE, 'gen test file'],
            ['enum', 'e', InputOption::VALUE_NONE, 'gen enum file'],
            ['force', 'f', InputOption::VALUE_NONE, 'force overwrite'],
        ];
    }

    /**
     * @return void
     * @throws ReflectionException
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public function handle(): void
    {
        DbalHelper::register();
        // 选项
        $options = $this->options();

        if ($options['enum']) {
            $this->makeEnum($this->argument('key'));
            return;
        }

        // 参数
        list($modules, $tableName, $modelName) = $this->getNames();
//        dump($modules, $tableName, $modelName);
        $table = TableHelper::GetTable($tableName);
        $columns = TableHelper::GetTableColumns($table);

        if ($options['migration'])
            $this->makeMigration($tableName);

        if ($options['model'])
            $this->makeModel($table, $columns, $modelName, $options);

        if ($options['controller'])
            $this->makeController($table, $columns, $modelName, $modules, $options);

        if ($options['test'])
            $this->makeTest($modelName, $modules, $options);
    }

    private function makeEnum(string $name)
    {
        $content = StubHelper::GetStub('Enum.stub');
        $content = StubHelper::ReplaceAll([
            'EnumName' => $name,
        ], $content);
        $filePath = app_path() . '/Enums/' . $name . '.php';
        $result = StubHelper::Save($filePath, $content);
        $this->line($result);
    }

    /**
     * @param string $tableName
     */
    private function makeMigration(string $tableName)
    {
        $this->call('make:migration', [
            'name' => "create_{$tableName}_table",
            '--create' => $tableName,
            '--table' => $tableName,
        ]);
    }

    /**
     * @param Table $table
     * @param array $columns
     * @param string $modelName
     * @param array $options
     */
    private function makeModel(Table $table, array $columns, string $modelName, array $options)
    {
        // BaseModel
        $stub = StubHelper::GetModelStub($table);
        $content = StubHelper::GetStub($stub);
        $content = StubHelper::ReplaceAll([
            'ModelProperties' => GenHelper::GenColumnsPropertiesString($table),
            'ModelMethods' => GenHelper::GenTableMethodsString(),
            'ModelName' => $modelName,
            'TableString' => GenHelper::GenTableString($table),
            'TableCommentString' => GenHelper::GenTableCommentString($table),
            'TableFillableString' => GenHelper::GenTableFillableString($columns),
            'ModelRelations' => GenHelper::GenTableRelations($table),
        ], $content);
        $filePath = app_path() . '/Models/Base/Base' . $modelName . '.php';
        $result = StubHelper::Save($filePath, $content, $options['force']);
        $this->line($result);

        // Model
        $content = StubHelper::GetStub('Model.stub');
        $content = StubHelper::ReplaceAll([
            'ModelName' => $modelName,
        ], $content);
        $filePath = $this->laravel['path'] . '/Models/' . $modelName . '.php';
        $result = StubHelper::Save($filePath, $content);
        $this->line($result);
    }

    /**
     * @param Table|null $table
     * @param array|null $columns
     * @param string $modelName
     * @param array $modules
     * @param array $options
     * @throws Exception
     */
    private function makeController(?Table $table, ?array $columns, string $modelName, array $modules, array $options)
    {
        if (count($modules) == 0)
            throw new Exception('need module name, eg：admin/admin');

        $hasSoftDelete = TableHelper::GetColumnsHasSoftDelete($table ? $table->getColumns() : []);
        $stubName = $hasSoftDelete ? "controllerWithSoftDelete.stub" : "controller.stub";

        $stubContent = StubHelper::GetStub($stubName);
        $stubContent = StubHelper::ReplaceAll([
            'ModelName' => $modelName,
            'TableComment' => $table ? $table->getComment() : '',
            'ModuleName' => implode('\\', $modules),
            'InsertString' => GenHelper::GenColumnsRequestValidateString($columns, "\t\t\t"),
        ], $stubContent);

        $moduleName = implode('/', $modules);
        $filePath = app_path() . "/Modules/$moduleName/{$modelName}Controller.php";
        $result = StubHelper::Save($filePath, $stubContent, $options['force']);
        $this->line($result);
    }

    /**
     * @param string $modelName
     * @param array $modules
     * @param array $options
     * @throws Err
     * @throws ReflectionException
     */
    private function makeTest(string $modelName, array $modules, array $options)
    {
        if (count($modules) == 0)
            Err::Throw('need module name. eg：admin/admin');

        $nameSpace = 'App\\Modules\\' . implode('\\', $modules) . '\\' . $modelName . 'Controller';
        $controllerFilePath = app_path() . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . implode('/', $modules) . '/' . $modelName . 'Controller.php';

        $class = new ReflectionClass($nameSpace);
        $cAttr = AttrHelper::GetControllerAttr($class);

        $content = GenHelper::GenTestContent($nameSpace, $controllerFilePath);

        $stubContent = StubHelper::GetStub('test.stub');
        $stubContent = StubHelper::ReplaceAll([
            'controllerIntro' => $cAttr->title,
            'ModelName' => $modelName,
            'ModuleName' => implode('\\', $modules),
            'content' => $content,
        ], $stubContent);
        $moduleName = implode('/', $modules);
        $filePath = $this->laravel['path'] . "/../tests/Modules/$moduleName/{$modelName}ControllerTest.php";
        $result = StubHelper::Save($filePath, $stubContent, $options['force']);
        $this->line($result);
    }

    /**
     * @return array
     */
    private function getNames(): array
    {
        $key = $this->argument('key');
        $key = str_replace('/', '\\', $key);
        $a = explode('\\', $key);
        $table = last($a);
        array_pop($a);
        $modules = array_map(function ($item) {
            return Str::of($item)->studly();
        }, $a);

        $tableName = (string)Str::of($table)->snake()->singular()->plural();
        $modelName = (string)Str::of($tableName)->studly();

        return [$modules, $tableName, $modelName];
    }
}
