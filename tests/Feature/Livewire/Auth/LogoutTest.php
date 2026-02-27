<?php

it('renders the logout component', function () {
    Livewire::test('auth.logout')
        ->assertSee('Expected content in the logout component');
});
