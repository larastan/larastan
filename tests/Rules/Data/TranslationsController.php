<?php

namespace Tests\Rules\Data;

class TranslationsController
{
    public function foo(): array
    {
        return [
            __('system.foo'),
            trans('system.bar')
        ];
    }
}
