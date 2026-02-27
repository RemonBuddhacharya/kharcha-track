<?php

it('renders the roles component', function () {
    Livewire::test('admin.roles.index')
        ->assertSee('Expected content in the roles component');
});
