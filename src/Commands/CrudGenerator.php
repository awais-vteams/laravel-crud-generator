<?php

namespace Ibex\CrudGenerator\Commands;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\select;

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
                            {stack : The development stack that should be installed (bootstrap,tailwind,livewire,api)}
                            {--route= : Custom route name}';

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

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'stack' => fn() => select(
                label: 'Which stack would you like to install?',
                options: [
                    'bootstrap' => 'Blade with Bootstrap css',
                    'tailwind' => 'Blade with Tailwind css',
                    'livewire' => 'Livewire with Tailwind css',
                    'api' => 'API only',
                    'jetstream'=> 'Jetstream inertia with Tailwind css',
                ],
                scroll: 5,
            ),
        ];
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        $this->options['stack'] = match ($input->getArgument('stack')) {
            'tailwind' => 'tailwind',
            'livewire' => 'livewire',
            'react' => 'react',
            'vue' => 'vue',
            'jetstream' => 'jetstream',
            default => 'bootstrap',
        };
    }

    protected function writeRoute(): static
    {
        $replacements = $this->buildReplacements();

        $this->info('Please add route below: i:e; web.php or api.php');

        $this->info('');

        $lines = match ($this->options['stack']) {
            'livewire' => [
                "Route::get('/{$this->_getRoute()}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Index::class)->name('{$this->_getRoute()}.index');",
                "Route::get('/{$this->_getRoute()}/create', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Create::class)->name('{$this->_getRoute()}.create');",
                "Route::get('/{$this->_getRoute()}/show/{{$replacements['{{modelNameLowerCase}}']}}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Show::class)->name('{$this->_getRoute()}.show');",
                "Route::get('/{$this->_getRoute()}/update/{{$replacements['{{modelNameLowerCase}}']}}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Edit::class)->name('{$this->_getRoute()}.edit');",
            ],
            'api' => [
                "Route::apiResource('".$this->_getRoute()."', {$this->name}Controller::class);",
            ],
            'jetstream' => [
                "Route::middleware(['auth:sanctum', 'verified'])->group(function () {",
                "    Route::resource('{$this->_getRoute()}', {$replacements['{{modelName}}']}" . "Controller::class);",
                "});"
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
        if($this->options['stack'] == 'jetstream') {
            $this->buildJetstream();

            return $this;
        }

        if ($this->options['stack'] == 'livewire') {
            $this->buildLivewire();

            return $this;
        }

        $controllerPath = $this->options['stack'] == 'api'
            ? $this->_getApiControllerPath($this->name)
            : $this->_getControllerPath($this->name);

        if ($this->files->exists($controllerPath) && $this->ask('Already exist Controller. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Controller ...');

        $replace = $this->buildReplacements();

        $stubFolder = match ($this->options['stack']) {
            'api' => 'api/',
            default => ''
        };

        $controllerTemplate = str_replace(
            array_keys($replace), array_values($replace), $this->getStub($stubFolder.'Controller')
        );

        $this->write($controllerPath, $controllerTemplate);

        if ($this->options['stack'] == 'api') {
            $resourcePath = $this->_getResourcePath($this->name);

            $resourceTemplate = str_replace(
                array_keys($replace), array_values($replace), $this->getStub($stubFolder.'Resource')
            );

            $this->write($resourcePath, $resourceTemplate);
        }

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

    protected function buildJetstream(): void
    {
        $this->info('Creating Jetstream Inertia Components ...');
    
        $folder = ucfirst(Str::plural($this->name));
        $replace = array_merge($this->buildReplacements(), $this->modelReplacements());
        
        // Generate Vue-specific replacements
        $formFields = '';
        $formData = '';
        $formEditData = '';
        $detailFields = '';
        $tableHead = '';
        $tableBody = '';
        $querySearch = '';

        $lowerModelName = strtolower($this->name);
        $capitalizeModelName = lcfirst($this->name);
        $isFirstColumn = true;
    
        foreach ($this->getColumnsWithType() as $column => $type) {
            $title = Str::title(str_replace('_', ' ', $column));
            
            // Generate Vue components specific fields
            $tableHead .= <<<HTML
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            $title
                                        </th>
                                        
    HTML;
    
            $tableBody .= <<<HTML
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ {$capitalizeModelName}.$column }}
                                        </td>
                                        
    HTML;
    
            $formFields .= $this->getJetstreamFormField($title, $column,$type);
            $formData .= "                $column: '',\n";
            $formEditData .= "                $column: this.{$capitalizeModelName}.$column,\n";
            $detailFields .= $this->getJetstreamDetailField($title, $column);
            if ($isFirstColumn) {
                $querySearch .= "\$query->where('{$column}', 'like', \"%{\$request->search}%\")\n";
                $isFirstColumn = false;
            } else {
                $querySearch .= "            ->orWhere('{$column}', 'like', \"%{\$request->search}%\")\n";
            }
        }
    
        // Add to replacements
        $replace['{{tableHeader}}'] = $tableHead;
        $replace['{{tableBody}}'] = $tableBody;
        $replace['{{formFields}}'] = $formFields;
        $replace['{{formData}}'] = $formData;
        $replace['{{formEditData}}'] = $formEditData;
        $replace['{{detailFields}}'] = $detailFields;
        $replace['{{querySearch}}'] = $querySearch;
    
        // Create Inertia component directory
        $componentPath = resource_path("js/Pages/{$folder}");
        if (!$this->files->isDirectory($componentPath)) {
            $this->files->makeDirectory($componentPath, 0755, true);
        }

        $this->createSharedComponents();
    
        // Generate the Inertia components
        foreach (['Index', 'Create', 'Edit', 'Show'] as $component) {
            $templatePath = $this->getStub("views/jetstream/{$component}", false);
    
            if ($this->files->exists($templatePath)) {
                $content = str_replace(
                    array_keys($replace),
                    array_values($replace),
                    $this->getStub("views/jetstream/{$component}")
                );
    
                $this->write("{$componentPath}/{$component}.vue", $content);
            } else {
                $this->warn("Stub for {$component} not found. Skipping...");
            }
        }
    
        // Create Controller
        $controllerPath = $this->_getControllerPath($this->name);
    
        if ($this->files->exists($controllerPath) && $this->ask('Already exist Controller. Do you want overwrite (y/n)?', 'y') == 'n') {
            return;
        }
    
        $this->info('Creating Controller for Jetstream...');
    
        $controllerTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('jetstream/Controller')
        );
    
        $this->write($controllerPath, $controllerTemplate);
    
        // Create Model
        $this->buildModel();
    }

    protected function createSharedComponents(): void
    {
        $componentsPath = resource_path('js/Components');
        
        // Check if Components directory exists, create if not
        if (!$this->files->isDirectory($componentsPath)) {
            $this->files->makeDirectory($componentsPath, 0755, true);
        }
        
        // Create Pagination component
        $paginationPath = $componentsPath.'/Pagination.vue';
        if (!$this->files->exists($paginationPath)) {
            $this->info('Creating Pagination component...');
            $paginationContent = $this->getPaginationComponent();
            $this->write($paginationPath, $paginationContent);
        }
        
        // Create SearchFilter component
        $searchFilterPath = $componentsPath.'/SearchFilter.vue';
        if (!$this->files->exists($searchFilterPath)) {
            $this->info('Creating SearchFilter component...');
            $searchFilterContent = $this->getSearchFilterComponent();
            $this->write($searchFilterPath, $searchFilterContent);
        }
    }

    protected function getPaginationComponent(): string
    {
        return <<<VUE
    <template>
        <div v-if="links.length > 3">
            <div class="flex flex-wrap -mb-1">
                <template v-for="(link, key) in links" :key="key">
                    <div 
                        v-if="link.url === null" 
                        class="mr-1 mb-1 px-4 py-2 text-sm text-gray-500 border rounded"
                        :class="{ 'opacity-50': link.url === null }"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-indigo-100"
                        :class="{
                            'bg-indigo-500 text-white': link.active,
                            'text-gray-700': !link.active
                        }"
                        :href="link.url"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </template>

    <script>
    import { Link } from '@inertiajs/vue3'
    export default {
    components: {
            Link
        },
        props: {
            links: Array
        }
    }
    </script>
    VUE;
    }

    protected function getSearchFilterComponent(): string
    {
        return <<<VUE
    <template>
        <div class="relative flex items-center">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input
                type="text"
                class="py-2 pl-10 pr-4 w-full text-sm text-gray-700 placeholder-gray-400 bg-white border border-gray-300 rounded focus:outline-none focus:border-indigo-500"
                placeholder="Search..."
                :value="modelValue"
                @input="updateValue"
            />
        </div>
    </template>

    <script>
    export default {
        props: {
            modelValue: String
        },
        emits: ['update:modelValue'],
        data() {
            return {
                timeout: null
            }
        },
        methods: {
            updateValue(e) {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    this.\$emit('update:modelValue', e.target.value);
                }, 300); // 300ms debounce
            }
        }
    }
    </script>
    VUE;
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
        if ($this->options['stack'] == 'api') {
            return $this;
        }

        $this->info('Creating Views ...');

        $tableHead = "\n";
        $tableBody = "\n";
        $viewRows = "\n";
        $form = "\n";
        
        // Add variables for Jetstream Vue components
        $formFields = "\n";
        $formData = "\n";
        $formEditData = "\n";
        $detailFields = "\n";

        foreach ($this->getColumnsWithType() as $column => $type) {
            
            $title = Str::title(str_replace('_', ' ', $column));

            $tableHead .= $this->getHead($title);
            $tableBody .= $this->getBody($column);
            if ($this->options['stack'] != 'jetstream') {
                $viewRows .= $this->getField($title, $column, 'view-field');
                $form .= $this->getField($title, $column);
            } else {
                $formFields .= $this->getJetstreamFormField($title, $column,$type);
                $formData .= "\t\t\t\t$column: '',\n";
                $formEditData .= "\t\t\t\t$column: this.{$this->name}.$column,\n";
                $detailFields .= $this->getJetstreamDetailField($title, $column);
            }
        }

        $replace = array_merge($this->buildReplacements(), [
            '{{tableHeader}}' => $tableHead,
            '{{tableBody}}' => $tableBody,
            '{{viewRows}}' => $viewRows,
            '{{form}}' => $form,
            '{{formFields}}' => $formFields,
            '{{formData}}' => $formData,
            '{{formEditData}}' => $formEditData,
            '{{detailFields}}' => $detailFields,
        ]);

        $this->buildLayout();

        if ($this->options['stack'] === 'jetstream') {
            return $this;
        }

        foreach (['index', 'create', 'edit', 'form', 'show'] as $view) {
            $path = $this->isCustomStubFolder()
                ? "views/{$this->options['stack']}/$view"
                : match ($this->options['stack']) {
                    'livewire' => $this->isLaravel12() ? "views/{$this->options['stack']}/12/$view" : "views/{$this->options['stack']}/default/$view",
                    default => "views/{$this->options['stack']}/$view"
                };

            $viewTemplate = str_replace(
                array_keys($replace), array_values($replace), $this->getStub($path)
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

    protected function mapColumnTypeToInputType(string $type): string
    {
        return match ($type) {
            'int','integer', 'bigint', 'smallint', 'tinyint' => 'number',
            'float', 'double', 'decimal' => 'number',
            'boolean' => 'checkbox',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime-local',
            'time' => 'time',
            'email' => 'email',
            'varchar', 'string' => 'text',
            'text', 'json'=> 'textarea',
            default => 'text',
        };
    }

    protected function getJetstreamFormField(string $title, string $column, string $type_column=""): string
    {
        $inputType = $this->mapColumnTypeToInputType(strtolower($type_column));
        
        return <<<HTML
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="$column">
                $title
            </label>
            <input
                id="$column"
                v-model="form.$column"
                type="$inputType"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                :class="{ 'border-red-500': form.errors.$column }"
            >
            <div v-if="form.errors.$column" class="text-red-500 text-xs italic">{{ form.errors.$column }}</div>
        </div>
        HTML;
    }

    protected function getJetstreamDetailField(string $title, string $column): string
    {
        $capitalizeModelName = lcfirst($this->name);
        return <<<HTML
        <div class="mb-4">
            <h3 class="text-gray-700 font-bold">$title:</h3>
            <p class="text-gray-600">{{ {$capitalizeModelName}.$column }}</p>
        </div>
        
    HTML;
    }
}
