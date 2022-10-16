<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreModel extends Model
{
    /**
     * get table name of model
     *
     * @return string
     */
    public static function getTableName()
    {
        return with(new static)->getTable();
    }
}
