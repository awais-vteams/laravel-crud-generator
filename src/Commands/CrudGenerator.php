<?php

namespace Ibex\CrudGenerator\Commands;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;

/**
 * Class CrudGenerator.
 *
 * @author  Awais <asargodha@gmail.com>
 */
class CrudGenerator extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud
                            {name : Table name}
                            {--route= : Custom route name}
                            {--stack= : The development stack that should be installed (blade,tailwind,livewire,react,vue)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Laravel CRUD operations';

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $this->info('Running Crud Generator ...');

        $this->table = $this->getNameInput();

        // If table not exist in DB return
        if (! $this->tableExists()) {
            $this->error("`$this->table` table not exist");

            return false;
        }

        // Build the class name from table name
        $this->name = $this->_buildClassName();

        // Generate the crud
        $this->buildOptions()
            ->buildController()
            ->buildModel()
            ->buildViews()
            ->writeRoute();

        $this->info('Created Successfully.');

        return true;
    }

    protected function writeRoute(): static
    {
        $replacements = $this->buildReplacements();

        $this->info('Please add route below: i:e; web.php');

        $this->info('');

        $lines = match ($this->options['stack']) {
            'livewire' => [
                "Route::get('/{$this->_getRoute()}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Index::class)->name('{$this->_getRoute()}.index');",
                "Route::get('/{$this->_getRoute()}/create', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Create::class)->name('{$this->_getRoute()}.create');",
                "Route::get('/{$this->_getRoute()}/show/{{$replacements['{{modelNameLowerCase}}']}}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Show::class)->name('{$this->_getRoute()}.show');",
                "Route::get('/{$this->_getRoute()}/update/{{$replacements['{{modelNameLowerCase}}']}}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Edit::class)->name('{$this->_getRoute()}.edit');",
            ],
            default => [
                "Route::resource('".$this->_getRoute()."', {$this->name}Controller::class);",
            ]
        };

        foreach ($lines as $line) {
            $this->info('<bg=blue;fg=white>'.$line.'</>');
        }

        $this->info('');

        return $this;
    }

    /**
     * Build the Controller Class and save in app/Http/Controllers.
     *
     * @return $this
     * @throws FileNotFoundException
     */
    protected function buildController(): static
    {
        if ($this->options['stack'] == 'livewire') {
            $this->buildLivewire();

            return $this;
        }

        $controllerPath = $this->_getControllerPath($this->name);

        if ($this->files->exists($controllerPath) && $this->ask('Already exist Controller. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Controller ...');

        $replace = $this->buildReplacements();

        $controllerTemplate = str_replace(
            array_keys($replace), array_values($replace), $this->getStub('Controller')
        );

        $this->write($controllerPath, $controllerTemplate);

        return $this;
    }

    protected function buildLivewire(): void
    {
        $this->info('Creating Livewire Component ...');

        $folder = ucfirst(Str::plural($this->name));
        $replace = array_merge($this->buildReplacements(), $this->modelReplacements());

        foreach (['Index', 'Show', 'Edit', 'Create'] as $component) {
            $componentPath = $this->_getLivewirePath($folder.'/'.$component);

            $componentTemplate = str_replace(
                array_keys($replace), array_values($replace), $this->getStub('livewire/'.$component)
            );

            $this->write($componentPath, $componentTemplate);
        }

        // Form
        $formPath = $this->_getLivewirePath('Forms/'.$this->name.'Form');

        $componentTemplate = str_replace(
            array_keys($replace), array_values($replace), $this->getStub('livewire/Form')
        );

        $this->write($formPath, $componentTemplate);
    }

    /**
     * @return $this
     * @throws FileNotFoundException
     *
     */
    protected function buildModel(): static
    {
        $modelPath = $this->_getModelPath($this->name);

        if ($this->files->exists($modelPath) && $this->ask('Already exist Model. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Model ...');

        // Make the models attributes and replacement
        $replace = array_merge($this->buildReplacements(), $this->modelReplacements());

        $modelTemplate = str_replace(
            array_keys($replace), array_values($replace), $this->getStub('Model')
        );

        $this->write($modelPath, $modelTemplate);

        // Make Request Class
        $requestPath = $this->_getRequestPath($this->name);

        $this->info('Creating Request Class ...');

        $requestTemplate = str_replace(
            array_keys($replace), array_values($replace), $this->getStub('Request')
        );

        $this->write($requestPath, $requestTemplate);

        return $this;
    }

    /**
     * @return $this
     * @throws FileNotFoundException
     *
     * @throws Exception
     */
    protected function buildViews(): static
    {
        $this->info('Creating Views ...');

        $tableHead = "\n";
        $tableBody = "\n";
        $viewRows = "\n";
        $form = "\n";

        foreach ($this->getFilteredColumns() as $column) {
            $title = Str::title(str_replace('_', ' ', $column));

            $tableHead .= $this->getHead($title);
            $tableBody .= $this->getBody($column);
            $viewRows .= $this->getField($title, $column, 'view-field');
            $form .= $this->getField($title, $column);
        }

        $replace = array_merge($this->buildReplacements(), [
            '{{tableHeader}}' => $tableHead,
            '{{tableBody}}' => $tableBody,
            '{{viewRows}}' => $viewRows,
            '{{form}}' => $form,
        ]);

        $this->buildLayout();

        foreach (['index', 'create', 'edit', 'form', 'show'] as $view) {
            $viewTemplate = str_replace(
                array_keys($replace), array_values($replace), $this->getStub("views/{$this->options['stack']}/$view")
            );

            $this->write($this->_getViewPath($view), $viewTemplate);
        }

        return $this;
    }

    /**
     * Make the class name from table name.
     *
     * @return string
     */
    private function _buildClassName(): string
    {
        return Str::studly(Str::singular($this->table));
    }
}
