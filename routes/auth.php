<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

$redirectToIdentityProvider = fn () => Inertia::location(
    Socialite::driver('oidc_timeliners')->redirect(),
);

Route::get('/login', $redirectToIdentityProvider)->name('login');
Route::get('/auth/redirect', $redirectToIdentityProvider)->name('auth.redirect');

Route::get('/auth/callback', function () {
    $oidcUser = Socialite::driver('oidc_timeliners')->user();

    $name = $oidcUser->getName();
    $email = $oidcUser->getEmail();

    abort_if(blank($name) || blank($email), 403, 'The identity provider did not return required account details.');

    $user = User::updateOrCreate([
        'sub' => $oidcUser->getId(),
    ], [
        'name' => $name,
        'email' => $email,
    ]);

    Auth::login($user);

    return redirect()->intended('/dashboard');
})->name('auth.callback');

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return to_route('home');
})->middleware('auth')->name('logout');
