<?php

test('app panel login page only shows google login action', function () {
    $response = $this->get('/app/login');

    $response->assertSuccessful();
    $response->assertSee('Login dengan Google');
    $response->assertSee(route('auth.social.redirect', ['provider' => 'google']), false);
    $response->assertDontSee('wire:model="data.email"', false);
    $response->assertDontSee('wire:model="data.password"', false);
});
