<?php

namespace Ibex\CrudGenerator;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Class ModelGenerator.
 */
class ModelGenerator
{
    private $functions = null;

    private $table;
    private $properties;
    private $modelNamespace;

    /**
     * ModelGenerator constructor.
     *
     * @param  string  $table
     * @param  string  $properties
     * @param  string  $modelNamespace
     */
    public function __construct(string $table, string $properties, string $modelNamespace)
    {
        $this->table = $table;
        $this->properties = $properties;
        $this->modelNamespace = $modelNamespace;
        $this->_init();
    }

    /**
     * Get all the eloquent relations.
     *
     * @return array
     */
    public function getEloquentRelations(): array
    {
        return [$this->functions, $this->properties];
    }

    private function _init(): void
    {
        foreach ($this->_getTableRelations() as $relation) {
            $this->functions .= $this->_getFunction($relation);
        }
    }

    private function _getFunction(array $relation): string
    {
        switch ($relation['name']) {
            case 'hasOne':
            case 'belongsTo':
                $this->properties .= "\n * @property {$relation['class']} \${$relation['relation_name']}";
                break;
            case 'hasMany':
                $this->properties .= "\n * @property ".$relation['class']."[] \${$relation['relation_name']}";
                break;
        }

        return '
    /**
     * @return \Illuminate\Database\Eloquent\Relations\\'.ucfirst($relation['name']).'
     */
    public function '.$relation['relation_name'].'()
    {
        return $this->'.$relation['name'].'(\\'.$this->modelNamespace.'\\'.$relation['class'].'::class, \''.$relation['foreign_key'].'\', \''.$relation['owner_key'].'\');
    }
    ';
    }

    /**
     * Get all relations from Table.
     *
     * @return array
     */
    private function _getTableRelations(): array
    {
        return [
            ...$this->getBelongsTo(),
            ...$this->getOtherRelations(),
        ];
    }

    /**
     * Extract the table name from a fully qualified table name (e.g., database.table).
     * @param  string  $foreignTable
     * @return string
     */
    protected function extractTableName(string $foreignTable): string
    {
        $dotPosition = strpos($foreignTable, '.');

        if ($dotPosition !== false) {
            return substr($foreignTable, $dotPosition + 1); // Extract table name only
        }

        return $foreignTable; // No dot found, return the original name
    }

    protected function getBelongsTo(): array
    {
        $relations = Schema::getForeignKeys($this->table);

        $eloquent = [];

        foreach ($relations as $relation) {
            if (count($relation['foreign_columns']) != 1 || count($relation['columns']) != 1) {
                continue;
            }

            $foreignTable = $this->extractTableName($table);

            $eloquent[] = [
                'name' => 'belongsTo',
                'relation_name' => Str::camel(Str::singular($foreignTable)),
                'class' => Str::studly(Str::singular($foreignTable)),
                'foreign_key' => $relation['columns'][0],
                'owner_key' => $relation['foreign_columns'][0],
            ];
        }

        return $eloquent;
    }

    protected function getOtherRelations(): array
    {
        $tables = Schema::getTableListing();
        $eloquent = [];

        foreach ($tables as $table) {
            $relations = Schema::getForeignKeys($table);
            $indexes = collect(Schema::getIndexes($table));

            foreach ($relations as $relation) {
                if ($relation['foreign_table'] != $this->table) {
                    continue;
                }

                if (count($relation['foreign_columns']) != 1 || count($relation['columns']) != 1) {
                    continue;
                }

                $isUniqueColumn = $this->getUniqueIndex($indexes, $relation['columns'][0]);
                $foreignTable = $this->extractTableName($table);

                $eloquent[] = [
                    'name' => $isUniqueColumn ? 'hasOne' : 'hasMany',
                    'relation_name' => Str::camel($isUniqueColumn ? Str::singular($foreignTable) : Str::plural($foreignTable)),
                    'class' => Str::studly(Str::singular($foreignTable)),
                    'foreign_key' => $relation['foreign_columns'][0],
                    'owner_key' => $relation['columns'][0],
                ];
            }
        }

        return $eloquent;
    }

    private function getUniqueIndex($indexes, $column): bool
    {
        $isUnique = false;

        foreach ($indexes as $index) {
            if (
                (count($index['columns']) == 1)
                && ($index['columns'][0] == $column)
                && $index['unique']
            ) {
                $isUnique = true;
                break;
            }
        }

        return $isUnique;
    }
}
