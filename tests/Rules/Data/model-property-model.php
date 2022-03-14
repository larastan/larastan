<?php

namespace ModelPropertyModel;

class ModelPropertyOnModel extends \Illuminate\Database\Eloquent\Model
{
    public function foo(): void
    {
        $this->update([
            'foo' => 'bar',
        ]);
    }

    public function unionMethod(\App\User|\App\Account $model): void
    {
        $model->update([
            'foo' => 'bar',
        ]);
    }

    public function unionMethodWithPropertyOnlyInOne(\App\User|\App\Account $model): void
    {
        $model->update(['name' => 'bar']);
    }

    public function unionMethodGreen(\App\User|\App\Account $model): void
    {
        $model->update(['id' => 5]);
    }
}
