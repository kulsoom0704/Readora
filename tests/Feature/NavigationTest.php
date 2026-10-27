<?php

use App\Models\User;

test('administrators see the account dropdown with profile, reservation, and management links', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    $response = $this->actingAs($admin)
        ->get(route('books.index'))
        ->assertOk()
        ->assertSee('Readora')
        ->assertSee('Home')
        ->assertSee('Books')
        ->assertSee('Categories')
        ->assertSee('Contact')
        ->assertSee('FAQ')
        ->assertSee('Edit Profile')
        ->assertSee('My Reservations')
        ->assertSee('Manage Users')
        ->assertSee('Manage Books')
        ->assertSee('Manage Reservations')
        ->assertSee('Log Out')
        ->assertSee('Account menu for '.$admin->name, false)
        ->assertSee(route('profile.edit'))
        ->assertSee(route('reservations.index'))
        ->assertSee(route('admin.users.index'))
        ->assertSee(route('admin.books.index'))
        ->assertSee(route('admin.reservations.index'))
        ->assertSee('aria-controls="account-menu-desktop"', false)
        ->assertSee('aria-controls="account-menu-mobile"', false)
        ->assertDontSee('admin-menu-desktop');

    expect(substr_count($response->getContent(), 'href="/faq"'))->toBe(2);
});

test('normal users see account links but never see admin management links', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('books.index'))
        ->assertOk()
        ->assertSee('FAQ')
        ->assertSee('Edit Profile')
        ->assertSee('My Reservations')
        ->assertSee('Log Out')
        ->assertSee(route('profile.edit'))
        ->assertDontSee('Manage Users')
        ->assertDontSee('Manage Books')
        ->assertDontSee('Manage Reservations');

    expect(substr_count($response->getContent(), 'href="/faq"'))->toBe(2);
});

test('guests see only the specified public links and login options', function () {
    $response = $this->get(route('books.index'))
        ->assertOk()
        ->assertSee('Home')
        ->assertSee('Books')
        ->assertSee('Categories')
        ->assertSee('Contact')
        ->assertSee('FAQ')
        ->assertSee('Login')
        ->assertSee('Register')
        ->assertDontSee('Edit Profile')
        ->assertDontSee('My Reservations')
        ->assertDontSee('Manage Users');

    expect(substr_count($response->getContent(), 'href="/faq"'))->toBe(2);
});
