<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User as AppUser;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('login sends an Inertia location response to the OIDC provider', function () {
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect('https://identity.example.com/authorize'));

    Socialite::shouldReceive('driver')
        ->once()
        ->with('oidc_timeliners')
        ->andReturn($provider);

    $this->withoutMiddleware(HandleInertiaRequests::class)
        ->withHeaders(['X-Inertia' => 'true'])
        ->get(route('login'))
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'https://identity.example.com/authorize');
});

test('OIDC callback creates and authenticates a local user', function () {
    $oidcUser = SocialiteUser::fake([
        'id' => 'timeliners-subject-123',
        'name' => 'OIDC User',
        'email' => 'oidc@example.com',
    ]);

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn($oidcUser);

    Socialite::shouldReceive('driver')
        ->once()
        ->with('oidc_timeliners')
        ->andReturn($provider);

    $this->get(route('auth.callback'))
        ->assertRedirect('/dashboard');

    $user = AppUser::where('sub', 'timeliners-subject-123')->firstOrFail();

    expect($user->name)->toBe('OIDC User')
        ->and($user->email)->toBe('oidc@example.com');

    $this->assertAuthenticatedAs($user);
});

test('password and registration endpoints are unavailable', function () {
    $this->post('/login')->assertMethodNotAllowed();
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});
