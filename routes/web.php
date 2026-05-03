<?php

use Illuminate\Support\Facades\Route;

// Landing page as home
Route::get('/', function () {
    return view('landing.index');
});

// Dashboard (after login)
/* Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified']);

// Auth routes (from Breeze)
require __DIR__.'/auth.php'; */