<?php

use Illuminate\Support\Facades\Route;
use Aliff\ChipIn\Http\Controllers\ChipInController;

Route::prefix('chipin')->name('chipin.')->group(function () {
    Route::get('/success', [ChipInController::class, 'success'])->name('success');
    Route::get('/failed', [ChipInController::class, 'failed'])->name('failed');
    Route::post('/callback', [ChipInController::class, 'callback'])->name('callback');
});

