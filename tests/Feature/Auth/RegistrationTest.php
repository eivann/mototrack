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

test('SignUp-002: full names containing digits or symbols are rejected', function () {
    foreach (['Juan123!', '12345', 'Juan@dela#Cruz', '<script>x</script>'] as $badName) {
        $this->post('/register', [
            'name' => $badName,
            'email' => 'valid.name@gmail.com',
            'phone' => '09123456789',
            'password' => 'MotoT#r@ck2026_Secure!',
            'password_confirmation' => 'MotoT#r@ck2026_Secure!',
        ])->assertSessionHasErrors('name');
    }

    $this->assertGuest();
});

test('SignUp-002: legitimate names with spaces, apostrophes, periods, hyphens and accents are accepted', function () {
    foreach (["Maria Clara", "Jose P. Rizal Jr.", "Ana-Liza O'Brien", "Zoë Muñoz"] as $i => $goodName) {
        $response = $this->post('/register', [
            'name' => $goodName,
            'email' => "good{$i}@gmail.com",
            'phone' => '09123456789',
            'password' => 'MotoT#r@ck2026_Secure!',
            'password_confirmation' => 'MotoT#r@ck2026_Secure!',
        ]);
        $response->assertSessionHasNoErrors();
        auth()->logout();
    }
});

test('SignUp-003: malformed and non-gmail emails are rejected', function () {
    foreach (['invalidemail', 'a@b', 'user@yahoo.com', 'no at@gmail.com'] as $badEmail) {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => $badEmail,
            'phone' => '09123456789',
            'password' => 'MotoT#r@ck2026_Secure!',
            'password_confirmation' => 'MotoT#r@ck2026_Secure!',
        ])->assertSessionHasErrors('email');
    }
});
