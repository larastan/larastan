<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelWithNonGenericCollection extends Model
{
    /**
     * @param  \App\ModelWithNonGenericCollection[]  $models
     */
    public function newCollection(array $models = []): NonGenericCollection
    {
        return new NonGenericCollection($models);
    }
}
