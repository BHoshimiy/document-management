<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the login screen to guests', function () {
    $this->get('/login')->assertOk()->assertSee('icofex');
});

it('registers a client together with their company', function () {
    $this->post('/register', [
        'username' => 'newfarm',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'company' => ['name' => 'Green Valley', 'inn' => '123456789'],
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    expect(User::firstWhere('username', 'newfarm')->role)->toBe(UserRole::Client);
});

it('logs a user in with valid credentials', function () {
    User::factory()->create(['username' => 'someone', 'password' => 'Password123!']);

    $this->post('/login', ['username' => 'someone', 'password' => 'Password123!'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

it('rejects a blocked account', function () {
    User::factory()->blocked()->create(['username' => 'banned', 'password' => 'Password123!']);

    $this->post('/login', ['username' => 'banned', 'password' => 'Password123!'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('redirects guests away from the dashboard', function () {
    $this->get('/')->assertRedirect(route('login'));
});
