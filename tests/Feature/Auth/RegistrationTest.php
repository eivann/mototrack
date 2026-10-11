<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@gmail.com',
        'phone' => '09123456789',
        'password' => 'MotoT#r@ck2026_Secure!',
        'password_confirmation' => 'MotoT#r@ck2026_Secure!',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/otp/verify');
});
