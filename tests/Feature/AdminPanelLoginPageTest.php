<?php

test('admin panel login page shows google login action', function () {
    $response = $this->get('/admin/login');

    $response->assertSuccessful();
    $response->assertSee('Login dengan Google');
    $response->assertSee(route('auth.social.redirect', ['provider' => 'google']), false);
});
