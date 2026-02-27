<?php

it('renders the dashboard component', function () {
    Livewire::test('dashboard')
        ->assertSee('Expected content in the dashboard component');
});
