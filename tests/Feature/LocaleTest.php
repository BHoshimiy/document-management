<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('offers both locales in the topbar and marks the current one', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('lang=en')
        ->assertSee('lang=ru')
        ->assertSee('class="active"', false);
});

it('switches the response language with the lang query parameter', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard', ['lang' => 'ru']))
        ->assertOk()
        ->assertSee(__('app.sign_out', [], 'ru'));
});

it('remembers the chosen locale on later requests', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get(route('dashboard', ['lang' => 'ru']))->assertOk();

    // No ?lang= this time — the session has to carry it.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('app.sign_out', [], 'ru'));
});

it('ignores an unsupported locale', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('dashboard', ['lang' => 'de']))
        ->assertOk()
        ->assertSee(__('app.sign_out', [], 'en'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('app.sign_out', [], 'en'));
});

it('signs out from the topbar and offers only one logout form', function () {
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect(substr_count($response->getContent(), route('logout')))->toBe(1);

    // fresh(): Auth::logout() touches remember_token, which a factory-built
    // model never hydrated, and shouldBeStrict() throws on missing attributes.
    $this->actingAs($user->fresh())
        ->post(route('logout'))
        ->assertRedirect();

    $this->assertGuest();
});
