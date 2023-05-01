<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * @param array<int, \App\Transaction> $models
     * @return \App\TransactionCollection<int, \App\Transaction>
     */
    public function newCollection(array $models = []): TransactionCollection
    {
        return new TransactionCollection($models);
    }
}
