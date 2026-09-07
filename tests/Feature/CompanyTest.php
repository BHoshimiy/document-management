<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the companies index as a table', function () {
    Company::factory()->create(['name' => 'Yosh Futbolchi LLC', 'inn' => '305123456']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.companies.index'))
        ->assertOk()
        ->assertSee('uploads-table')
        ->assertDontSee('doc-card')
        ->assertSee('Yosh Futbolchi LLC')
        ->assertSee('305123456');
});

it('shows a client only their own company', function () {
    $client = User::factory()->create();
    Company::factory()->for($client)->create(['name' => 'Mine LLC']);
    Company::factory()->create(['name' => 'Someone Else LLC']);

    $this->actingAs($client)
        ->get(route('admin.companies.index'))
        ->assertOk()
        ->assertSee('Mine LLC')
        ->assertDontSee('Someone Else LLC');
});

it('shows every company to an admin', function () {
    Company::factory()->create(['name' => 'Alpha LLC']);
    Company::factory()->create(['name' => 'Beta LLC']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.companies.index'))
        ->assertOk()
        ->assertSee('Alpha LLC')
        ->assertSee('Beta LLC');
});

it('blocks a client from viewing another company', function () {
    $company = Company::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.companies.show', $company))
        ->assertForbidden();
});

it('lets an admin edit any company', function () {
    $company = Company::factory()->create(['name' => 'Old Name', 'inn' => '111111111']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.companies.update', $company), [
            'name' => 'New Name',
            'inn' => '222222222',
            'address' => 'Tashkent',
        ])
        ->assertRedirect(route('admin.companies.index'))
        ->assertSessionHas('status');

    expect($company->refresh()->name)->toBe('New Name')
        ->and($company->inn)->toBe('222222222');
});

it('keeps its own inn when a company is re-saved unchanged', function () {
    $company = Company::factory()->create(['inn' => '333333333']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.companies.update', $company), [
            'name' => $company->name,
            'inn' => '333333333',
        ])
        ->assertRedirect(route('admin.companies.index'))
        ->assertSessionHasNoErrors();
});

it('rejects an inn already used by another company', function () {
    Company::factory()->create(['inn' => '444444444']);
    $company = Company::factory()->create(['inn' => '555555555']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.companies.update', $company), [
            'name' => $company->name,
            'inn' => '444444444',
        ])
        ->assertSessionHasErrors('inn');
});

it('forbids a client from editing another company', function () {
    $company = Company::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.companies.edit', $company))
        ->assertForbidden();
});

it('deletes a company that has no documents', function () {
    $company = Company::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.companies.destroy', $company))
        ->assertRedirect(route('admin.companies.index'))
        ->assertSessionHas('status');

    expect($company->refresh()->trashed())->toBeTrue();
});

it('refuses to delete a company that still has documents', function () {
    $company = Company::factory()->create();
    Document::factory()->for($company)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.companies.destroy', $company))
        ->assertSessionHasErrors('company');

    expect($company->refresh()->trashed())->toBeFalse();
});

it('forbids a moderator from deleting a company', function () {
    $company = Company::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->delete(route('admin.companies.destroy', $company))
        ->assertForbidden();
});

it('lets a client save their own profile without changing the inn', function () {
    $client = User::factory()->create();
    $company = Company::factory()->for($client)->create(['inn' => '666666666']);

    $this->actingAs($client)
        ->put(route('profile.update'), [
            'name' => $company->name,
            'inn' => '666666666',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');
});
