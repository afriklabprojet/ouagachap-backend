<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::post('/contact', [LandingController::class, 'contact'])->name('contact');
Route::get('/legal/{slug}', [LegalPageController::class, 'show'])->name('legal.show');
Route::get('/faq', [LegalPageController::class, 'faq'])->name('faq');

// Alias SEO-friendly pour Meta / Facebook — Data Deletion Instructions URL
Route::get('/suppression-donnees', fn () => redirect()->route('legal.show', ['slug' => \App\Models\LegalPage::SLUG_DELETION]))->name('data.deletion');

