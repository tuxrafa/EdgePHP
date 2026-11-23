<?php

test('the application redirects root to the author ui', function () {
    $response = $this->get('/');

    $response->assertRedirect('/author/');
});
