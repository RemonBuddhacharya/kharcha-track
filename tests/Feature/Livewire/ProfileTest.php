<?php

it('renders the profile component', function () {
    Livewire::test('profile')
        ->assertSee('Expected content in the profile component');
});
