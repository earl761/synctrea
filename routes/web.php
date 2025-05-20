<?php

use App\Http\Controllers\AmazonAuthController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

Route::get('amazon/auth/{destination}', [AmazonAuthController::class, 'redirect'])->name('amazon.auth');
Route::get('amazon/callback', [AmazonAuthController::class, 'callback'])->name('amazon.callback');

Route::post(
    'stripe/webhook',
    [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']
)->name('cashier.webhook');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Main pages
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/about', function () {
    return view('about', [
        'teamMembers' => collect([
            (object)[
                'name' => 'Sarah Johnson',
                'position' => 'CEO & Founder',
                'photo_url' => asset('images/team/sarah.jpg'),
                'twitter' => 'https://twitter.com/sarahjohnson',
                'linkedin' => 'https://linkedin.com/in/sarahjohnson'
            ],
            // Add more team members here
        ])
    ]);
})->name('about');

// Blog routes
Route::prefix('blog')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('/{slug}', [BlogController::class, 'show'])->name('blog.show');
});

// Supplier routes
Route::prefix('suppliers')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/register', [SupplierController::class, 'register'])->name('suppliers.register');
    Route::get('/{id}', [SupplierController::class, 'show'])->name('suppliers.show');
});

// Contact routes
Route::get('/contact', function () {
    $settings = app(App\Settings\GeneralSettings::class);
    return view('contact', compact('settings'));
})->name('contact');

Route::post('/contact/submit', [ContactController::class, 'submit'])->name('contact.submit');

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

// Email verification routes
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
