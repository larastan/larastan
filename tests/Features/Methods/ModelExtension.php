<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Illuminate\Database\Eloquent\Model;

class ModelExtension
{
    public function testWhere(): Thread
    {
        return (new Thread)->where(['foo' => 'bar']);
    }

    public function testStaticWhere(): Thread
    {
        return Thread::where(['foo' => 'bar']);
    }

    public function testDynamicWhere(): Thread
    {
        return (new Thread)->whereFoo('bar');
    }

    public function testStaticDynamicWhere(): Thread
    {
        return Thread::whereFoo('bar');
    }
}

class Thread extends Model
{
}
