<?php

namespace Ztech243\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Ztech243\CrudGenerator\Services\ModelParserService;

class MakeCrudApiCommand extends Command
{
    // Signature of the command, with options for generating migration and model
    protected $signature = 'make:crud-api {name} {--migration} {--model}';
    
    // Description of the command
    protected $description = 'Create CRUD API operations for a model';
    
    // The parser service to extract model details
    protected $parserService;

    /**
     * Constructor method.
     *
     * @param ModelParserService $parserService
     */
    public function __construct(ModelParserService $parserService)
    {
        parent::__construct();
        $this->parserService = $parserService;
    }

    /**
     * Handle the command execution.
     */
    public function handle()
    {
        // Get the model name argument
        $name = $this->argument('name');
        $this->info('Generating CRUD API for model: ' . $name);

        // Check if migration and model generation are required
        $generateMigration = $this->option('migration');
        $generateModel = $this->option('model');

        $modelClass = "App\\Models\\{$name}";

        // Get fillable attributes if model is to be generated
        if ($generateModel) {
            $fillableAttributes = $this->parserService->getFillableAttributes($modelClass);
        } else {
            $fillableAttributes = [];
        }
        
        // Get table columns
        $tableColumns = $this->parserService->getTableColumns($modelClass);

        // Create controller
        $this->createController($name, $fillableAttributes);

        // Create model if required
        if ($generateModel) {
            $this->createModel($name);
        }

        // Create migration if required
        if ($generateMigration) {
            $this->createMigration($name, $tableColumns);
        }

        // Create request and resource classes
        $this->createRequest($name, $tableColumns);
        $this->createResource($name, $tableColumns);

        // Add route
        $this->addRoute($name);

        $this->info('CRUD API generation completed.');
    }

    /**
     * Create a controller for the model.
     *
     * @param string $name
     * @param array $fillableAttributes
     */
    protected function createController($name, $fillableAttributes)
    {
        $controllerTemplate = str_replace(
            ['{{modelName}}', '{{modelNamePluralLowerCase}}', '{{fillableAttributes}}'],
            [$name, strtolower(Str::plural($name)), implode(', ', $fillableAttributes)],
            $this->getStub('api-controller')
        );

        $this->ensureDirectoryExists(app_path("/Http/Controllers/Api"));

        file_put_contents(app_path("/Http/Controllers/Api/{$name}Controller.php"), $controllerTemplate);
    }

    /**
     * Create a model for the given name.
     *
     * @param string $name
     */
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

    /**
     * Create a migration for the model.
     *
     * @param string $name
     * @param array $tableColumns
     */
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

    /**
     * Create request classes for the model.
     *
     * @param string $name
     * @param array $tableColumns
     */
    protected function createRequest($name, $tableColumns)
    {
        $rules = $this->generateValidationRules($tableColumns);

        $storeRequestTemplate = str_replace(
            ['{{modelName}}', '{{rules}}'],
            [$name, $rules],
            $this->getStub('api-request')
        );

        $this->ensureDirectoryExists(app_path("/Http/Requests"));

        file_put_contents(app_path("/Http/Requests/Store{$name}Request.php"), $storeRequestTemplate);

        $updateRequestTemplate = str_replace(
            ['{{modelName}}', '{{rules}}'],
            [$name, $rules],
            $this->getStub('api-request')
        );

        file_put_contents(app_path("/Http/Requests/Update{$name}Request.php"), $updateRequestTemplate);
    }

    /**
     * Create a resource class for the model.
     *
     * @param string $name
     * @param array $tableColumns
     */
    protected function createResource($name, $tableColumns)
    {
        $resourceFields = $this->generateResourceFields($tableColumns);

        $resourceTemplate = str_replace(
            ['{{modelName}}', '{{resourceFields}}'],
            [$name, $resourceFields],
            $this->getStub('api-resource')
        );

        $this->ensureDirectoryExists(app_path("/Http/Resources"));

        file_put_contents(app_path("/Http/Resources/{$name}Resource.php"), $resourceTemplate);
    }

    /**
     * Add a route for the model's CRUD API.
     *
     * @param string $name
     */
    protected function addRoute($name)
    {
        $routeTemplate = "Route::apiResource('".strtolower(Str::plural($name))."', App\Http\Controllers\Api\\".$name."Controller::class);";
        file_put_contents(base_path('routes/api.php'), $routeTemplate.PHP_EOL, FILE_APPEND);
    }

    /**
     * Ensure the given directory exists; if it doesn't, create it.
     *
     * @param string $path
     */
    protected function ensureDirectoryExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @param string $type
     * @return string
     */
    protected function getStub($type)
    {
        $stubPath = resource_path("stubs/vendor/crud-generator/{$type}.stub");
        if (!file_exists($stubPath)) {
            $stubPath = __DIR__ . "/../../stubs/{$type}.stub";
        }
        return file_get_contents($stubPath);
    }

    /**
     * Format the table columns for the migration file.
     *
     * @param array $columns
     * @return string
     */
    protected function formatTableColumns($columns)
    {
        $formattedColumns = [];
        foreach ($columns as $column => $type) {
            $formattedColumns[] = "\$table->{$type}('{$column}');";
        }
        return implode("\n", $formattedColumns);
    }

    /**
     * Generate validation rules for the request classes.
     *
     * @param array $columns
     * @return string
     */
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

    /**
     * Generate resource fields for the resource class.
     *
     * @param array $columns
     * @return string
     */
    protected function generateResourceFields($columns)
    {
        $fields = [];
        foreach ($columns as $column => $type) {
            $fields[] = "'{$column}' => \$this->{$column},";
        }
        return implode("\n", $fields);
    }
}
