<?php

test('the application redirects root to app panel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/app');
});
