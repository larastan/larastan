<?php

class ModelPropertyOnModel extends \Illuminate\Database\Eloquent\Model
{
    public function foo(): void
    {
        $this->update([
            'foo' => 'bar',
        ]);
    }
}
