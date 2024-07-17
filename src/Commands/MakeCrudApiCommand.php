<?php

namespace Ztech243\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Ztech243\CrudGenerator\Services\ModelParserService;

class MakeCrudApiCommand extends Command
{
    // Signature de la commande avec des options pour générer une migration et un modèle
    protected $signature = 'make:crud-api {name} {--migration} {--model}';
    
    // Description de la commande
    protected $description = 'Create CRUD API operations for a model';
    
    // Service de parsing des modèles
    protected $parserService;

    /**
     * Constructeur.
     *
     * @param ModelParserService $parserService
     */
    public function __construct(ModelParserService $parserService)
    {
        parent::__construct();
        $this->parserService = $parserService;
    }

    /**
     * Exécution de la commande.
     */
    public function handle()
    {
        // Récupérer le nom du modèle à partir des arguments
        $name = $this->argument('name');
        $this->info('Generating CRUD API for model: ' . $name);

        // Vérifier si la génération de migration et de modèle est nécessaire
        $generateMigration = $this->option('migration');
        $generateModel = $this->option('model');

        $modelClass = "App\\Models\\{$name}";

        // Récupérer les attributs fillables si le modèle doit être généré
        if ($generateModel) {
            $fillableAttributes = $this->parserService->getFillableAttributes($modelClass);
        } else {
            $fillableAttributes = [];
        }
        
        // Récupérer les colonnes de la table
        $tableColumns = $this->parserService->getTableColumns($modelClass);

        // Créer le contrôleur
        $this->createController($name, $fillableAttributes);

        // Créer le modèle si nécessaire
        if ($generateModel) {
            $this->createModel($name);
        }

        // Créer la migration si nécessaire
        if ($generateMigration) {
            $this->createMigration($name, $tableColumns);
        }

        // Créer les classes de requête et les ressources
        $this->createRequest($name, $tableColumns);
        $this->createResource($name, $tableColumns);

        // Ajouter la route
        $this->addRoute($name);

        $this->info('CRUD API generation completed.');
    }

    /**
     * Crée un contrôleur pour le modèle.
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
     * Crée un modèle pour le nom donné.
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
     * Crée une migration pour le modèle.
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
     * Crée des classes de requête pour le modèle.
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

        $this->ensureDirectoryExists(app_path("/Http/Requests/Api"));

        file_put_contents(app_path("/Http/Requests/Api/Store{$name}Request.php"), $storeRequestTemplate);

        $updateRequestTemplate = str_replace(
            ['{{modelName}}', '{{rules}}'],
            [$name, $rules],
            $this->getStub('api-update-request')
        );

        file_put_contents(app_path("/Http/Requests/Api/Update{$name}Request.php"), $updateRequestTemplate);
    }

    /**
     * Crée une classe de ressource pour le modèle.
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
     * Ajoute une route pour le CRUD du modèle.
     *
     * @param string $name
     */
    protected function addRoute($name)
    {
        $routeTemplate = "Route::apiResource('".strtolower(Str::plural($name))."', App\Http\Controllers\Api\\".$name."Controller::class);";
        file_put_contents(base_path('routes/api.php'), $routeTemplate.PHP_EOL, FILE_APPEND);
    }

    /**
     * Assure que le répertoire donné existe; sinon, le crée.
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
     * Récupère le stub pour le type spécifié.
     *
     * @param string $type
     * @return string
     */
    protected function getStub($type)
    {
        $stubPath = resource_path("stubs/vendor/crud-generator/{$type}.stub");
        if (!file_exists($stubPath)) {
            $stubPath = __DIR__ . "/../stubs/{$type}.stub";
        }
        return file_get_contents($stubPath);
    }

    /**
     * Formate les colonnes de la table pour le fichier de migration.
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
     * Génère les règles de validation pour les classes de requête.
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
     * Génère les champs de ressource pour la classe de ressource.
     *
     * @param array $columns
     * @return string
     */
    protected function generateResourceFields($columns)
    {
        $fields = [];
        foreach ($columns as $column => $type) {
            if ($column !== 'deleted_at') {
                $fields[] = "'{$column}' => \$this->{$column},";
            }
        }
        return implode("\n", $fields);
    }
}
