<?php

namespace Dscheff\CrudGenerator\GridView\Controllers;

use App\Http\Controllers\Controller;

class GridViewController extends Controller
{
    protected $sort = '';
    protected $sort_icon = '';
    protected static $skip_columns = ['sort', 'sort_order', 'page'];

    protected function applyFilters($model)
    {
        $pairs = \request()->query();

        $models = (new $model())->where([
            [function ($query) use ($pairs) {
                foreach ($pairs as $column => $val) {
                    if ($val != '' && !in_array($column, self::$skip_columns)) {
                        $query->where($column, 'LIKE', '%'.$val.'%')->get();
                    }
                }
            }],
        ]);

        if (isset($pairs['sort'])) {
            $sort_order = 'asc';
            if (isset($pairs['sort_order'])) {
                $sort_order = $pairs['sort_order'];
            }
            $models->orderBy($pairs['sort'], $sort_order);
        }

        return $models;
        // select join related_table rt on rt.id = mt.rt_id
    }

    protected static function sortIcon($get)
    {
        if (isset($get['sort_order']) && $get['sort_order'] !== '') {
            if ($get['sort_order'] === 'asc') {
                return 'down';
            }

            return 'up';
        }

        return '';
    }
}
