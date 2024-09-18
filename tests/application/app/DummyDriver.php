<?php

namespace App;


class DummyDriver implements DummyDriverContract
{
    public function type(): string {
        return 'dummy';
    }

    public function publicMethodNotInContract(): string {
        return 'Hello World';
    }
}
