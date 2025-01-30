<?php

function string_param(string $param): void
{
    // Do something with param
}

$ulidModel = \App\UlidModel::firstOrFail();
$uuidModel = \App\UuidModel::firstOrFail();

string_param($ulidModel->id);
string_param($uuidModel->id);
