<?php

use App\User;
use Illuminate\Auth\Access\Response;

function testGood(User $viewer): Response
{
    return Response::allow();
}

function testBad(?User $viewer): Response
{
    if (!$viewer) Response::deny(); // Should raise an error
    return Response::allow();
}
