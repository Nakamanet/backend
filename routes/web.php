<?php

use Illuminate\Support\Facades\Route;



use Dedoc\Scramble\Scramble;

Scramble::registerUiRoute(path: 'docs/api');
Scramble::registerJsonSpecificationRoute(path: 'docs/api.json');

