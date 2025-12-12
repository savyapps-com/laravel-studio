<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

// Laravel auth redirect - defaults to admin panel login
Route::get('/admin/login', fn () => view('spa'))->name('login');

// Vue.js SPA - All panel routes (pattern validated in AppServiceProvider)
Route::get('/{panel}/{any?}', fn () => view('spa'))->where('any', '.*')->name('spa');
