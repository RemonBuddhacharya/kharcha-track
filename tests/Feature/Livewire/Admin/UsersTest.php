<?php

it('renders the users component', function () {
    Livewire::test('admin.users.index')
        ->assertSee('Expected content in the users component');
});
