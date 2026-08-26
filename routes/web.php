<?php

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return redirect()->to(Filament::getPanel('admin')->getUrl());
})->name('home');
