<?php

use App\Http\Controllers\ComputerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/say/{message?}', [SiteController::class, 'say'])
    ->name('site.say');

Route::get('/entry', [SiteController::class, 'entry'])
    ->name('entry.form');

Route::post('/entry', [SiteController::class, 'entryStore'])
    ->name('entry.store');

Route::view('/about', 'site.about')->name('site.about');

Route::get('/contact', [ContactController::class, 'index'])
    ->name('contact.index');

Route::post('/contact', [ContactController::class, 'store'])
    ->name('contact.store');

Route::resource('country', CountryController::class);

Route::resource('computers', ComputerController::class);
