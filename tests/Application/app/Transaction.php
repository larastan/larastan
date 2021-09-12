<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * @param  array<int, Transaction>  $models
     * @return TransactionCollection<Transaction>
     */
    public function newCollection(array $models = []): TransactionCollection
    {
        return new TransactionCollection($models);
    }
}
