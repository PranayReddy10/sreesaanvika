<?php

use App\Http\Controllers\PhotographPreviewController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\BagController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Webhooks\RazorpayWebhookController;
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
Route::post('/saree/{product:slug}/review', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('review.store');

Route::post('/contact', [EnquiryController::class, 'store'])->name('enquiry.store');

Route::get('/bag', [BagController::class, 'show'])->name('bag');
Route::post('/bag/add', [BagController::class, 'add'])->name('bag.add');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
Route::post('/checkout/verify', [CheckoutController::class, 'verify'])->name('checkout.verify');
Route::post('/checkout/failed', [CheckoutController::class, 'failed'])->name('checkout.failed');

// Signed, and good for a week: an order number alone must never show a
// stranger somebody's name, address and telephone number.
Route::get('/order/{order}', [CheckoutController::class, 'confirmation'])->name('order.confirmed');

/*
 * Razorpay's own word on what happened, which is what the shop believes.
 * No session and no CSRF token — it is a server talking to a server, and it
 * proves itself with a signature instead.
 */
Route::post('/webhooks/razorpay', RazorpayWebhookController::class)
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])
    ->name('webhooks.razorpay');

Route::get('/track', [AccountController::class, 'track'])->name('track');
Route::post('/track', [AccountController::class, 'find'])->name('track.find');

/*
 * An account is optional — a shopper can buy without one — so these are for
 * people who want their orders and saved sarees kept.
 */
Route::middleware('guest')->group(function () {
    Route::get('/sign-in', [SessionController::class, 'create'])->name('sign-in');
    Route::post('/sign-in', [SessionController::class, 'store']);

    Route::get('/join', [RegisterController::class, 'create'])->name('join');
    Route::post('/join', [RegisterController::class, 'store']);
});

Route::post('/sign-out', [SessionController::class, 'destroy'])->name('sign-out');

Route::get('/account', [AccountController::class, 'index'])->name('account');
Route::get('/account/saved', [AccountController::class, 'wishlist'])->name('account.wishlist');
Route::post('/account/saved/{product}', [AccountController::class, 'save'])
    ->middleware('auth')
    ->name('account.save');

// The addresses her own orders have been sent to.
Route::post('/account/address/{address}', [AccountController::class, 'useAddress'])
    ->middleware('auth')
    ->name('account.address.use');
Route::delete('/account/address/{address}', [AccountController::class, 'forgetAddress'])
    ->middleware('auth')
    ->name('account.address.forget');

Route::get('/page/{slug}', PageController::class)->name('page');

Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter');
Route::get('/newsletter/leave', [NewsletterController::class, 'leave'])->name('newsletter.leave');

/*
 * What the shop tells search engines and shopping services about itself.
 * Built on request: the catalogue is small enough that a cached file would
 * only be one more thing to go quietly stale.
 */
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/feed/google.xml', [FeedController::class, 'google'])->name('feed.google');

/*
 * A photograph handed to the admin from this address rather than the Space's.
 *
 * The upload boxes fetch their pictures, and a browser polices a fetch across
 * domains — so without this a shop on DigitalOcean sees every picture on the
 * shop and a grey "Loading" bar in the admin.
 */
Route::get('/photograph-preview/{path}', PhotographPreviewController::class)
    ->where('path', '.*')
    ->middleware('auth')
    ->name('photograph.preview');
