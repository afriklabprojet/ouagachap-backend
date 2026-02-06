<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalPageController;
use Illuminate\Support\Facades\Route;

// Landing page dynamique
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::post('/contact', [LandingController::class, 'contact'])->name('contact');

// Pages légales
Route::get('/legal/{slug}', [LegalPageController::class, 'show'])->name('legal.show');
Route::get('/faq', [LegalPageController::class, 'faq'])->name('faq');
