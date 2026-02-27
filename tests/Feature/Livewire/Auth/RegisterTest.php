<?php

it('renders the register component', function () {
    Livewire::test('auth.register')
        ->assertSee('Expected content in the register component');
});
