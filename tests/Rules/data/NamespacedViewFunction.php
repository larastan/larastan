<?php

namespace Tests\Rules\Data\NamespacedViewFunction;

function view(string $name): string
{
    return $name;
}

class NamespacedViewFunctionController
{
    public function index(): string
    {
        return view('unused');
    }
}
