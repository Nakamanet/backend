<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


use Dedoc\Scramble\Scramble;

Scramble::registerUiRoute(path: 'docs/api');
Scramble::registerJsonSpecificationRoute(path: 'docs/api.json');

