<?php

namespace Ibex\CrudGenerator\Commands;

use Exception;
use Ibex\CrudGenerator\ModelGenerator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

/**
 * Class GeneratorCommand.
 */
abstract class GeneratorCommand extends Command implements PromptsForMissingInput
{
    protected Filesystem $files;

    /**
     * Do not make these columns fillable in Model or views.
     *
     * @var array
     */
    protected array $unwantedColumns = [
        'id',
        'uuid',
        'ulid',
        'password',
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected ?string $table = null;

    /**
     * Formatted Class name from Table.
     *
     * @var null|string
     */
    protected $name = null;

    private ?array $tableColumns = null;

    protected string $modelNamespace = 'App\Models';

    protected string $controllerNamespace = 'App\Http\Controllers';

    protected string $apiControllerNamespace = 'App\Http\Controllers\Api';

    protected string $resourceNamespace = 'App\Http\Resources';

    protected string $livewireNamespace = 'App\Livewire';

    protected string $requestNamespace = 'App\Http\Requests';

    protected string $layout = 'layouts.app';

    protected array $options = [];

    /**
     * Create a new controller creator command instance.
     *
     * @param  Filesystem  $files
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->unwantedColumns = config('crud.model.unwantedColumns', $this->unwantedColumns);
        $this->modelNamespace = config('crud.model.namespace', $this->modelNamespace);
        $this->controllerNamespace = config('crud.controller.namespace', $this->controllerNamespace);
        $this->apiControllerNamespace = config('crud.controller.apiNamespace', $this->apiControllerNamespace);
        $this->resourceNamespace = config('crud.resources.namespace', $this->resourceNamespace);
        $this->livewireNamespace = config('crud.livewire.namespace', $this->livewireNamespace);
        $this->requestNamespace = config('crud.request.namespace', $this->requestNamespace);
        $this->layout = config('crud.layout', $this->layout);
    }

    /**
     * Generate the controller.
     *
     * @return $this
     */
    abstract protected function buildController(): static;

    /**
     * Generate the Model.
     *
     * @return $this
     */
    abstract protected function buildModel(): static;

    /**
     * Generate the views.
     *
     * @return $this
     */
    abstract protected function buildViews(): static;

    /**
     * Build the directory if necessary.
     *
     * @param  string  $path
     *
     * @return string
     */
    protected function makeDirectory(string $path): string
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Write the file/Class.
     *
     * @param $path
     * @param $content
     */
    protected function write($path, $content): void
    {
        $this->makeDirectory($path);

        $this->files->put($path, $content);
    }

    /**
     * Get the stub file.
     *
     * @param  string  $type
     * @param  boolean  $content
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function getStub(string $type, bool $content = true): string
    {
        $stub_path = config('crud.stub_path', 'default');

        if (blank($stub_path) || $stub_path == 'default') {
            $stub_path = __DIR__.'/../stubs/';
        }

        $path = Str::finish($stub_path, '/')."$type.stub";

        if (! $content) {
            return $path;
        }

        return $this->files->get($path);
    }

    /**
     * @param  int  $no
     *
     * @return string
     */
    private function _getSpace(int $no = 1): string
    {
        return str_repeat("\t", $no);
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getControllerPath($name): string
    {
        return app_path($this->_getNamespacePath($this->controllerNamespace)."{$name}Controller.php");
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getApiControllerPath($name): string
    {
        return app_path($this->_getNamespacePath($this->apiControllerNamespace)."{$name}Controller.php");
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getResourcePath($name): string
    {
        return app_path($this->_getNamespacePath($this->resourceNamespace)."{$name}Resource.php");
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getLivewirePath($name): string
    {
        return app_path($this->_getNamespacePath($this->livewireNamespace)."{$name}.php");
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getRequestPath($name): string
    {
        return app_path($this->_getNamespacePath($this->requestNamespace)."{$name}Request.php");
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getModelPath($name): string
    {
        return $this->makeDirectory(app_path($this->_getNamespacePath($this->modelNamespace)."$name.php"));
    }

    /**
     * Get the path from namespace.
     *
     * @param $namespace
     *
     * @return string
     */
    private function _getNamespacePath($namespace): string
    {
        $str = Str::start(Str::finish(Str::after($namespace, 'App'), '\\'), '\\');

        return str_replace('\\', '/', $str);
    }

    /**
     * Get the default layout path.
     *
     * @return string
     */
    private function _getLayoutPath(): string
    {
        return $this->makeDirectory(resource_path("/views/layouts/app.blade.php"));
    }

    /**
     * @param $view
     *
     * @return string
     */
    protected function _getViewPath($view): string
    {
        $name = Str::kebab($this->name);
        $path = match ($this->options['stack']) {
            'livewire' => "/views/livewire/$name/$view.blade.php",
            default => "/views/$name/$view.blade.php"
        };

        return $this->makeDirectory(resource_path($path));
    }

    /**
     * Build the replacement.
     *
     * @return array
     */
    protected function buildReplacements(): array
    {
        return [
            '{{layout}}' => $this->layout,
            '{{modelName}}' => $this->name,
            '{{modelTitle}}' => Str::title(Str::snake($this->name, ' ')),
            '{{modelTitlePlural}}' => Str::title(Str::snake(Str::plural($this->name), ' ')),
            '{{modelNamespace}}' => $this->modelNamespace,
            '{{controllerNamespace}}' => $this->controllerNamespace,
            '{{apiControllerNamespace}}' => $this->apiControllerNamespace,
            '{{resourceNamespace}}' => $this->resourceNamespace,
            '{{requestNamespace}}' => $this->requestNamespace,
            '{{livewireNamespace}}' => $this->livewireNamespace,
            '{{modelNamePluralLowerCase}}' => Str::camel(Str::plural($this->name)),
            '{{modelNamePluralUpperCase}}' => ucfirst(Str::plural($this->name)),
            '{{modelNameLowerCase}}' => Str::camel($this->name),
            '{{modelRoute}}' => $this->_getRoute(),
            '{{modelView}}' => Str::kebab($this->name),
        ];
    }

    protected function _getRoute()
    {
        return $this->options['route'] ?? Str::kebab(Str::plural($this->name));
    }

    /**
     * Build the form fields for form.
     *
     * @param $title
     * @param $column
     * @param  string  $type
     *
     * @return string
     * @throws FileNotFoundException
     *
     */
    protected function getField($title, $column, string $type = 'form-field'): string
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
            '{{column}}' => $column,
            '{{column_snake}}' => Str::snake($column),
        ]);

        return str_replace(
            array_keys($replace), array_values($replace), $this->getStub("views/{$this->options['stack']}/$type")
        );
    }

    /**
     * @param $title
     *
     * @return string
     */
    protected function getHead($title): string
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
        ]);

        $attr = match ($this->options['stack']) {
            'tailwind', 'livewire' => 'scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"',
            default => ''
        };

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(9).'<th '.$attr.'>{{title}}</th>'."\n"
        );
    }

    /**
     * @param $column
     *
     * @return string
     */
    protected function getBody($column): string
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{column}}' => $column,
        ]);

        $attr = match ($this->options['stack']) {
            'tailwind', 'livewire' => 'class="whitespace-nowrap px-3 py-4 text-sm text-gray-500"',
            default => ''
        };

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(10).'<td '.$attr.'>{{ ${{modelNameLowerCase}}->{{column}} }}</td>'."\n"
        );
    }

    /**
     * Make layout if not exists.
     *
     * @throws Exception
     */
    protected function buildLayout(): void
    {
        if (! (view()->exists($this->layout))) {

            $this->info('Creating Layout ...');

            $uiPackage = match ($this->options['stack']) {
                'tailwind', 'livewire', 'react', 'vue' => 'laravel/breeze',
                default => 'laravel/ui'
            };

            if (! $this->requireComposerPackages([$uiPackage], true)) {
                throw new Exception("Unable to install $uiPackage. Please install it manually");
            }

            $uiCommand = match ($this->options['stack']) {
                'tailwind' => 'php artisan breeze:install blade',
                'livewire' => 'php artisan breeze:install livewire',
                'react' => 'php artisan breeze:install react',
                'vue' => 'php artisan breeze:install vue',
                default => 'php artisan ui bootstrap --auth'
            };

            $this->runCommands([$uiCommand]);
        }
    }

    /**
     * Get the DB Table columns.
     *
     * @return array|null
     */
    protected function getColumns(): ?array
    {
        if (empty($this->tableColumns)) {
            $this->tableColumns = Schema::getColumns($this->table);
        }

        return $this->tableColumns;
    }

    /**
     * @return array
     */
    protected function getFilteredColumns(): array
    {
        $unwanted = $this->unwantedColumns;
        $columns = [];

        foreach ($this->getColumns() as $column) {
            $columns[] = $column['name'];
        }

        return array_filter($columns, function ($value) use ($unwanted) {
            return ! in_array($value, $unwanted);
        });
    }

    /**
     * Make model attributes/replacements.
     *
     * @return array
     */
    protected function modelReplacements(): array
    {
        $properties = '*';
        $livewireFormProperties = '';
        $livewireFormSetValues = '';
        $rulesArray = [];
        $softDeletesNamespace = $softDeletes = '';
        $modelName = Str::camel($this->name);

        foreach ($this->getColumns() as $column) {
            $properties .= "\n * @property \${$column['name']}";

            if (! in_array($column['name'], $this->unwantedColumns)) {
                $livewireFormProperties .= "\n    public \${$column['name']} = '';";
                $livewireFormSetValues .= "\n        \$this->{$column['name']} = \$this->{$modelName}Model->{$column['name']};";
            }

            if (! $column['nullable']) {
                $rulesArray[$column['name']] = ['required'];
            }

            if ($column['type_name'] == 'bool') {
                $rulesArray[$column['name']][] = 'boolean';
            }

            if ($column['type_name'] == 'uuid') {
                $rulesArray[$column['name']][] = 'uuid';
            }

            if ($column['type_name'] == 'text' || $column['type_name'] == 'varchar') {
                $rulesArray[$column['name']][] = 'string';
            }

            if ($column['name'] == 'deleted_at') {
                $softDeletesNamespace = "use Illuminate\Database\Eloquent\SoftDeletes;\n";
                $softDeletes = "use SoftDeletes;\n";
            }
        }

        $rules = function () use ($rulesArray) {
            $rules = '';
            // Exclude the unwanted rulesArray
            $rulesArray = Arr::except($rulesArray, $this->unwantedColumns);
            // Make rulesArray
            foreach ($rulesArray as $col => $rule) {
                $rules .= "\n\t\t\t'$col' => '".implode('|', $rule)."',";
            }

            return $rules;
        };

        $fillable = function () {

            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "'".$value."'";
            });

            // CSV format
            return implode(', ', $filterColumns);
        };

        $properties .= "\n *";

        [$relations, $properties] = (new ModelGenerator($this->table, $properties, $this->modelNamespace))->getEloquentRelations();

        return [
            '{{fillable}}' => $fillable(),
            '{{rules}}' => $rules(),
            '{{relations}}' => $relations,
            '{{properties}}' => $properties,
            '{{softDeletesNamespace}}' => $softDeletesNamespace,
            '{{softDeletes}}' => $softDeletes,
            '{{livewireFormProperties}}' => $livewireFormProperties,
            '{{livewireFormSetValues}}' => $livewireFormSetValues,
        ];
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput(): string
    {
        return trim($this->argument('name'));
    }

    /**
     * Build the options
     *
     * @return $this
     */
    protected function buildOptions(): static
    {
        $this->options['route'] = null;
        $this->options['stack'] = $this->argument('stack');

        return $this;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the table'],
        ];
    }

    protected function tableExists(): bool
    {
        return Schema::hasTable($this->table);
    }

    /**
     * Installs the given Composer Packages into the application.
     *
     * @param  array  $packages
     * @param  bool  $asDev
     * @return bool
     */
    protected function requireComposerPackages(array $packages, bool $asDev = false): bool
    {
        $command = array_merge(
            ['composer', 'require'],
            $packages,
            $asDev ? ['--dev'] : [],
        );

        return (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
                ->setTimeout(null)
                ->run(function ($type, $output) {
                    $this->output->write($output);
                }) === 0;
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function runCommands(array $commands): void
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->output->write('    '.$line);
        });
    }
}
