<?php

use Illuminate\Support\Facades\Route;

Route::get('/chipin', fn() => view('chipin::welcome'));
