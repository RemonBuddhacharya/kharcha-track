<?php

it('renders the permissions component', function () {
    Livewire::test('admin.permissions.index')
        ->assertSee('Expected content in the permissions component');
});
