<?php

namespace Ibex\CrudGenerator\Commands;

use Ibex\CrudGenerator\ModelGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class GeneratorCommand
 * @package Ibex\CrudGenerator\Commands
 */
abstract class GeneratorCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Do not make these columns fillable in Model or views
     *
     * @var array
     */
    protected $unwantedColumns = [
        'id',
        'password',
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Table name from argument
     *
     * @var string
     */
    protected $table = null;

    /**
     * Formatted Class name from Table
     *
     * @var string
     */
    protected $name = null;

    /**
     * Store the DB table columns
     *
     * @var array
     */
    private $tableColumns = null;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Generate the controller.
     *
     * @return $this
     */
    abstract protected function buildController();

    /**
     * Generate the Model.
     *
     * @return $this
     */
    abstract protected function buildModel();

    /**
     * Generate the views.
     *
     * @return $this
     */
    abstract protected function buildViews();

    /**
     * Build the directory if necessary.
     *
     * @param  string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Get the stub file.
     *
     * @param $type
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getStub($type)
    {
        return $this->files->get(__DIR__ . "/../stubs/{$type}.stub");
    }

    /**
     * Write the file/Class
     *
     * @param $path
     * @param $content
     */
    protected function write($path, $content)
    {
        $this->files->put($path, $content);
    }

    /**
     * @param $no
     * @return string
     */
    private function _getSpace($no = 1)
    {
        $tabs = "";
        for ($i = 0; $i < $no; $i++) {
            $tabs .= "\t";
        }
        return $tabs;
    }

    /**
     * @param $name
     * @return string
     */
    protected function _getControllerPath($name)
    {
        return app_path("/Http/Controllers/{$name}Controller.php");
    }

    /**
     * @param $name
     * @return string
     */
    protected function _getModelPath($name)
    {
        return $this->makeDirectory(app_path("/{$name}.php"));
    }

    /**
     * @param $view
     * @return string
     */
    protected function _getViewPath($view)
    {
        $name = strtolower($this->name);
        return $this->makeDirectory(resource_path("/views/{$name}/{$view}.blade.php"));
    }

    /**
     * Build the replacement.
     *
     * @return array
     */
    protected function buildReplacements()
    {
        return [
            '{{modelName}}' => $this->name,
            '{{modelNamePluralLowerCase}}' => strtolower(str_plural($this->name)),
            '{{modelNamePluralUpperCase}}' => ucfirst(str_plural($this->name)),
            '{{modelNameLowerCase}}' => strtolower($this->name)
        ];
    }

    /**
     * Build the form fields for form
     *
     * @param $title
     * @param $column
     * @param string $type
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getField($title, $column, $type = 'form-field')
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
            '{{column}}' => $column,
        ]);

        return str_replace(
            array_keys($replace), array_values($replace), $this->getStub("views/{$type}")
        );
    }

    /**
     * @param $title
     * @return mixed
     */
    protected function getHead($title)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(10) . '<th>{{title}}</th>' . "\n"
        );
    }

    /**
     * @param $column
     * @return mixed
     */
    protected function getBody($column)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{column}}' => $column,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(11) . '<td>{{ ${{modelNameLowerCase}}->{{column}} }}</td>' . "\n"
        );
    }

    /**
     * Get the DB Table columns
     *
     * @return array
     */
    protected function getColumns()
    {
        if (empty($this->tableColumns)) {
            $this->tableColumns = DB::select('SHOW COLUMNS FROM ' . $this->table);
        }

        return $this->tableColumns;
    }

    /**
     * @return array
     */
    protected function getFilteredColumns()
    {
        $unwanted = $this->unwantedColumns;
        $columns = [];

        foreach ($this->getColumns() as $column) {
            $columns[] = $column->Field;
        }

        return array_filter($columns, function ($value) use ($unwanted) {
            return !in_array($value, $unwanted);
        });
    }

    /**
     * Make model attributes/replacements.
     *
     * @return array
     */
    protected function modelReplacements()
    {
        $properties = '*';
        $rulesArray = [];

        foreach ($this->getColumns() as $value) {
            $properties .= "\n * @property $$value->Field";

            if ($value->Null == "NO") {
                $rulesArray[$value->Field] = 'required';
            }
        }

        $rules = function () use ($rulesArray) {
            $rules = '';
            // Exclude the unwanted rulesArray
            $rulesArray = array_except($rulesArray, $this->unwantedColumns);
            // Make rulesArray
            foreach ($rulesArray as $col => $rule) {
                $rules .= "\n\t\t'{$col}' => '{$rule}',";
            }

            return $rules;
        };

        $fillable = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "'" . $value . "'";
            });

            // CSV format
            return implode(',', $filterColumns);
        };

        $properties .= "\n *";

        list($relations, $properties) = (new ModelGenerator($this->table, $properties))->getEloquentRelations();

        return [
            '{{modelName}}' => $this->name,
            '{{fillable}}' => $fillable(),
            '{{rules}}' => $rules(),
            '{{relations}}' => $relations,
            '{{properties}}' => $properties,
        ];
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the table'],
        ];
    }

    /**
     * Is Table exist in DB
     *
     * @return mixed
     */
    protected function tableExists()
    {
        return Schema::hasTable($this->table);
    }
}