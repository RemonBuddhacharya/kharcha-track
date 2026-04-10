<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('logs out authenticated users and redirects them to the landing page', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/logout')
        ->assertRedirect(route('landing'));

    $this->assertGuest();
});

it('redirects guests away from the logout route', function () {
    get('/logout')->assertRedirect(route('login'));
});
