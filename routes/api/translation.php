<?php

use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

// Public translation endpoint for anime/manga descriptions, characters, and metadata
Route::post('/translate', [TranslationController::class, 'translate']);
