<?php

beforeEach(function () {
    $this->artisan('db:seed');
});

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
