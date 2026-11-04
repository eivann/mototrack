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
test('RD2-R1: curly apostrophe in a name is accepted (matches server regex)', function () {
    $response = $this->post('/register', [
        'name' => "Ana\u{2019}Liza O\u{2019}Brien",
        'email' => 'curly@gmail.com',
        'phone' => '09123456789',
        'password' => 'MotoT#r@ck2026_Secure!',
        'password_confirmation' => 'MotoT#r@ck2026_Secure!',
    ]);
    $response->assertSessionHasNoErrors();
});

test('RD2-R1: accepted and rejected phone-number formats', function () {
    // Accepted: 09XXXXXXXXX and +639XXXXXXXXX.
    foreach ([['09171234567', 'ok-a'], ['+639171234567', 'ok-b']] as [$phone, $slug]) {
        $this->post('/register', [
            'name' => 'Phone Tester',
            'email' => "{$slug}@gmail.com",
            'phone' => $phone,
            'password' => 'MotoT#r@ck2026_Secure!',
            'password_confirmation' => 'MotoT#r@ck2026_Secure!',
        ])->assertSessionHasNoErrors();
        auth()->logout();
    }

    // Rejected: letters, too short, too long, wrong prefix.
    foreach (['0917abc4567', '0917123', '091712345678', '639171234567', '9171234567'] as $bad) {
        $this->post('/register', [
            'name' => 'Phone Tester',
            'email' => 'badphone@gmail.com',
            'phone' => $bad,
            'password' => 'MotoT#r@ck2026_Secure!',
            'password_confirmation' => 'MotoT#r@ck2026_Secure!',
        ])->assertSessionHasErrors('phone');
    }
    $this->assertGuest();
});

// RD2-R5: the phone field must not truncate a pasted formatted number before the sanitizer normalises it
// (a maxlength of 11 cut "+63 917 123 4567" to "+63 917 123"), and the sanitizer must write the cleaned value to the field.
test('RD2-R5: phone input does not pre-truncate pastes and sanitizer writes the cleaned value', function () {
    $html = $this->get('/register')->assertOk()->getContent();
    expect($html)->not->toContain('maxlength="11"');
    expect($html)->toContain('e.target.value = clean');
});
