<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BagController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

/*
 * The shop's own addresses.
 *
 * A saree lives at /saree/{slug} and nowhere else — no category in the path,
 * because the shop has no categories, and a web address that promises a
 * hierarchy the shop does not have is one that breaks the day it changes.
 */

Route::get('/', HomeController::class)->name('home');

Route::get('/sarees', ShopController::class)->name('shop');
Route::get('/saree/{product:slug}', ProductController::class)->name('product');

Route::get('/bag', [BagController::class, 'show'])->name('bag');
Route::post('/bag/add', [BagController::class, 'add'])->name('bag.add');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');

Route::get('/track', [AccountController::class, 'track'])->name('track');
Route::post('/track', [AccountController::class, 'find'])->name('track.find');

Route::get('/account', [AccountController::class, 'index'])->name('account');
Route::get('/account/saved', [AccountController::class, 'wishlist'])->name('account.wishlist');

Route::get('/page/{slug}', PageController::class)->name('page');
