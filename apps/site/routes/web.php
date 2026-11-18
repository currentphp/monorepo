<?php

declare(strict_types=1);

use App\Docs\DocsCatalog;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

foreach (app(DocsCatalog::class)->routable() as $guide) {
    Route::get($guide->path, GuideController::class)->name($guide->routeName());
}

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

// GitHub Pages serves dist/404.html for unknown paths.
Route::view('404.html', 'not-found')->name('not-found');
