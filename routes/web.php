<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MockCheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/about-us', [PublicContentController::class, 'about'])->name('about');
Route::get('/conferences', [PublicContentController::class, 'conferences'])->name('conferences');
Route::get('/news', [PublicContentController::class, 'news'])->name('news');
Route::get('/eminent-scientists', [PublicContentController::class, 'scientists'])->name('scientists');
Route::get('/gallery', [PublicContentController::class, 'gallery'])->name('gallery');
Route::get('/contact-us', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact-us', [ContactController::class, 'store'])->name('contact.store');

Route::get('/membership/join', [MembershipController::class, 'create'])->name('membership.join');
Route::post('/membership/join', [MembershipController::class, 'store'])->name('membership.store');
Route::get('/membership/renew', [MembershipController::class, 'renewCreate'])->name('membership.renew');
Route::post('/membership/renew', [MembershipController::class, 'renew'])->name('membership.renew.store');
Route::get('/membership/directory', [MembershipController::class, 'directory'])->name('membership.directory');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::post('/events/{event:slug}/register', [EventController::class, 'register'])->name('events.register');

Route::get('/donate', [DonationController::class, 'create'])->name('donate.create');
Route::post('/donate', [DonationController::class, 'store'])->name('donate.store');

Route::get('/payments/{payment}/success', [PaymentController::class, 'success'])->name('payments.success');
Route::get('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
Route::get('/payments/{payment}/mock-checkout', MockCheckoutController::class)->name('payments.mock');
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');
