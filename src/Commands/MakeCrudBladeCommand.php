<?php

namespace Ztech243\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Ztech243\CrudGenerator\Services\ModelParserService;

class MakeCrudBladeCommand extends Command
{
    protected $signature = 'make:crud-blade {name} {--migration} {--model}';
    protected $description = 'Create CRUD Blade operations for a model';
    protected $parserService;

    public function __construct(ModelParserService $parserService)
    {
        parent::__construct();
        $this->parserService = $parserService;
    }

    public function handle()
    {
        $name = $this->argument('name');
        $this->info('Generating CRUD Blade for model: ' . $name);

        $generateMigration = $this->option('migration');
        $generateModel = $this->option('model');

        $modelClass = "App\\Models\\{$name}";
        if ($generateModel) {
            $fillableAttributes = $this->parserService->getFillableAttributes($modelClass);
        } else {
            $fillableAttributes = []; // Handle case when model is not generated
        }
        
        $tableColumns = $this->parserService->getTableColumns($modelClass);

        $this->createController($name, $fillableAttributes);

        if ($generateModel) {
            $this->createModel($name);
        }

        if ($generateMigration) {
            $this->createMigration($name, $tableColumns);
        }

        $this->createRequest($name, $tableColumns);
        $this->createViews($name, $fillableAttributes);
        $this->addRoute($name);

        $this->info('CRUD Blade generation completed.');
    }

    protected function createController($name, $fillableAttributes)
    {
        $controllerTemplate = str_replace(
            ['{{modelName}}', '{{modelNamePluralLowerCase}}', '{{fillableAttributes}}'],
            [$name, strtolower(Str::plural($name)), implode(', ', $fillableAttributes)],
            $this->getStub('blade-controller')
        );

        $this->ensureDirectoryExists(app_path("/Http/Controllers"));

        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $controllerTemplate);
    }

    protected function createModel($name)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            $this->getStub('model')
        );

        $this->ensureDirectoryExists(app_path("/Models"));

        file_put_contents(app_path("/Models/{$name}.php"), $modelTemplate);
    }

    protected function createMigration($name, $tableColumns)
    {
        $tableName = strtolower(Str::plural($name));
        $migrationName = 'create_' . $tableName . '_table';
        $migrationFile = date('Y_m_d_His') . '_' . $migrationName . '.php';

        $migrationTemplate = str_replace(
            ['{{tableName}}', '{{tableColumns}}'],
            [$tableName, $this->formatTableColumns($tableColumns)],
            $this->getStub('migration')
        );

        $this->ensureDirectoryExists(database_path("/migrations"));

        file_put_contents(database_path("/migrations/{$migrationFile}"), $migrationTemplate);
    }

    protected function createRequest($name, $tableColumns)
    {
        $rules = $this->generateValidationRules($tableColumns);

        $storeRequestTemplate = str_replace(
            ['{{modelName}}', '{{rules}}'],
            [$name, $rules],
            $this->getStub('blade-request')
        );

        $this->ensureDirectoryExists(app_path("/Http/Requests"));

        file_put_contents(app_path("/Http/Requests/Store{$name}Request.php"), $storeRequestTemplate);

        $updateRequestTemplate = str_replace(
            ['{{modelName}}', '{{rules}}'],
            [$name, $rules],
            $this->getStub('blade-update-request')
        );

        file_put_contents(app_path("/Http/Requests/Update{$name}Request.php"), $updateRequestTemplate);
    }

    protected function createViews($name, $fillableAttributes)
    {
        $views = ['index', 'create', 'edit', 'show'];
        foreach ($views as $view) {
            $viewTemplate = str_replace(
                ['{{modelName}}', '{{modelNamePluralLowerCase}}', '{{fillableAttributes}}'],
                [$name, strtolower(Str::plural($name)), implode(', ', $fillableAttributes)],
                $this->getStub("views/{$view}")
            );

            $this->ensureDirectoryExists(resource_path("views/{$name}"));

            file_put_contents(resource_path("views/{$name}/{$view}.blade.php"), $viewTemplate);
        }
    }

    protected function addRoute($name)
    {
        $routeTemplate = "Route::resource('".strtolower(Str::plural($name))."', App\Http\Controllers\\".$name."Controller::class);";
        file_put_contents(base_path('routes/web.php'), $routeTemplate.PHP_EOL, FILE_APPEND);
    }

    protected function getStub($type)
    {
        $stubPath = resource_path("stubs/vendor/crud-generator/{$type}.stub");
        if (!file_exists($stubPath)) {
            $stubPath = __DIR__ . "/../stubs/{$type}.stub";
        }
        return file_get_contents($stubPath);
    }

    protected function ensureDirectoryExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    protected function formatTableColumns($columns)
    {
        $formattedColumns = [];
        foreach ($columns as $column => $type) {
            $formattedColumns[] = "\$table->{$type}('{$column}');";
        }
        return implode("\n", $formattedColumns);
    }

    protected function generateValidationRules($columns)
    {
        $rules = [];
        foreach ($columns as $column => $type) {
            if ($column !== 'id' && $column !== 'created_at' && $column !== 'updated_at') {
                $rules[] = "'{$column}' => 'required',";
            }
        }
        return implode("\n", $rules);
    }
}
