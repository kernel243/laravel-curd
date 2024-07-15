<?php

namespace Ztech243\CrudGenerator\Services;

use Illuminate\Support\Facades\Schema;

class ModelParserService
{
    public function getFillableAttributes($modelClass)
    {
        $model = new $modelClass;
        return $model->getFillable();
    }

    public function getTableColumns($modelClass)
    {
        $model = new $modelClass;
        $columns = Schema::getColumnListing($model->getTable());
        $columnDetails = [];
        
        foreach ($columns as $column) {
            $columnDetails[$column] = Schema::getColumnType($model->getTable(), $column);
        }

        return $columnDetails;
    }
}
