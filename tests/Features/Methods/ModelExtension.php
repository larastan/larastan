<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ModelExtension
{
    /**
     * @return iterable<\App\User>|\Illuminate\Database\Eloquent\Collection
     */
    public function testAll()
    {
        return User::all();
    }

    /**
     * @return \App\User
     */
    public function testReturnThis(): User
    {
        $user = User::join('tickets.tickets', 'tickets.tickets.id', '=', 'tickets.sale_ticket.ticket_id')
            ->where(['foo' => 'bar']);

        return $user;
    }

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
        return (new Thread)->whereFoo(['bar']);
    }

    public function testStaticDynamicWhere(): Thread
    {
        return Thread::whereFoo(['bar']);
    }
}

class Thread extends Model
{
}
