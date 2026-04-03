<?php

foreach ([
    'auth',
    'users',
    'posts',
    'likes',
    'forum',
    'friendships',
    'library',
] as $file) {
    require_once __DIR__ . '/api/' . $file . '.php';
}
