<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeCrud extends Command
{
    protected $signature = 'crud:make {name} {--fields=}';
    protected $description = 'Create CRUD operations for a given model';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('name');
        $fields = $this->option('fields');

        $this->createMigration($name, $fields);
        $this->createModel($name);
        $this->createController($name);
        $this->createRoutes($name);
        $this->createViews($name);

        $this->info('CRUD operations created successfully.');
    }

    protected function createMigration($name, $fields)
    {
        $tableName = Str::plural(Str::snake($name));
        $migrationName = 'create_' . $tableName . '_table';
        $filePath = base_path('database/migrations/' . date('Y_m_d_His') . '_' . $migrationName . '.php');
        $stubPath = base_path('stubs\migration.stub');

        $fieldsArray = explode(';', $fields);
        $fieldStrings = [];
        foreach ($fieldsArray as $field) {
            list($fieldName, $fieldType) = explode(' ', $field);
            $fieldStrings[] = "\$table->$fieldType('$fieldName');";
        }
        $fieldsStub = implode("\n            ", $fieldStrings);

        $stub = File::get($stubPath);
        $stub = str_replace(['{{tableName}}', '{{fields}}'], [$tableName, $fieldsStub], $stub);

        File::put($filePath, $stub);
    }


    protected function createModel($name)
    {
        $modelName = ucfirst($name);
        $filePath = app_path("Models/{$modelName}.php");
        $stubPath = base_path('stubs\model.stub');

        $stub = File::get($stubPath);
        $stub = str_replace('{{modelName}}', $modelName, $stub);

        File::put($filePath, $stub);
    }


    protected function createController($name)
    {
        $controllerName = ucfirst($name) . 'Controller';
        $modelName = ucfirst($name);
        $filePath = app_path("Http/Controllers/{$controllerName}.php");
        $stubPath = base_path('stubs\controller.stub');

        $stub = File::get($stubPath);
        $stub = str_replace(['{{controllerName}}', '{{modelName}}'], [$controllerName, $modelName], $stub);

        File::put($filePath, $stub);
    }


    protected function createRoutes($name)
    {
        $tableName = Str::plural(Str::snake($name));
        $routeFile = base_path('routes/web.php');
        $route = "Route::resource('$tableName', App\\Http\\Controllers\\" . ucfirst($name) . "Controller::class);";

        File::append($routeFile, "\n" . $route);
    }


    protected function createViews($name)
    {
        $tableName = Str::plural(Str::snake($name));
        $viewPath = base_path("views/{$tableName}");
        File::makeDirectory($viewPath, 0755, true);

        $stubPath = base_path('stubs\view');
        $viewFiles = ['index.stub', 'create.stub', 'edit.stub', 'show.stub'];

        foreach ($viewFiles as $viewFile) {
            $filePath = "{$viewPath}/" . str_replace('.stub', '.blade.php', $viewFile);
            $stub = File::get("{$stubPath}/{$viewFile}");
            $stub = str_replace('{{tableName}}', $tableName, $stub);
            File::put($filePath, $stub);
        }
    }

}
