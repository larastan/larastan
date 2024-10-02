<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

try {
    Http::get('http://localhost:3000/health');
} catch (ConnectionException) {
    return false;
}
