<?php

$hosts = explode(',', env('SCOPE_HOSTS', 'a,b'));

return [
    'hosts' => $hosts,
];
