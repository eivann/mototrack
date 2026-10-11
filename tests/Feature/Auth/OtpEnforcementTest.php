<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('authenticated user without verified OTP cannot access protected dashboard and is redirected to OTP verify', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    // Explicitly unverified session independent of TestCase::actingAs default
    $response = $this->actingAs($owner)
        ->withSession(['otp_verified' => false])
        ->get(route('dashboard'));

    $response->assertRedirect(route('otp.verify'));
});

test('authenticated customer without verified OTP is redirected to OTP verify when accessing customer dashboard', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $response = $this->actingAs($customer)
        ->withSession(['otp_verified' => false])
        ->get(route('customer.dashboard'));

    $response->assertRedirect(route('otp.verify'));
});

test('unverified user is permitted to view the OTP verify screen and logout without redirect loop', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['otp_verified' => false])
        ->get(route('otp.verify'));

    $response->assertOk();
    $response->assertViewIs('auth.otp-verify');

    // Can still logout
    $logoutResponse = $this->actingAs($user)
        ->withSession(['otp_verified' => false])
        ->post(route('logout'));

    $this->assertGuest();
    $logoutResponse->assertRedirect('/');
});

test('submitting valid OTP successfully completes verification and unlocks access', function () {
    $otp = '123456';
    $user = User::factory()->create([
        'role' => 'owner',
        'otp_code' => Hash::make($otp),
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['otp_verified' => false])
        ->post(route('otp.verify'), [
            'otp_code' => $otp,
        ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertEquals(true, session('otp_verified'));
    
    // User OTP fields cleared
    $user->refresh();
    expect($user->otp_code)->toBeNull()
        ->and($user->otp_expires_at)->toBeNull();
});

test('submitting invalid or expired OTP rejects verification', function () {
    $user = User::factory()->create([
        'otp_code' => Hash::make('654321'),
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['otp_verified' => false])
        ->post(route('otp.verify'), [
            'otp_code' => '000000', // Invalid code
        ]);

    $response->assertSessionHasErrors(['otp_code']);
    $this->assertNotEquals(true, session('otp_verified'));
});

test('unverified user can update their email address and receive a new OTP', function () {
    \Illuminate\Support\Facades\Mail::fake();

    $user = User::factory()->create([
        'email' => 'wrong_email@example.com',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['otp_verified' => false])
        ->post(route('otp.update-email'), [
            'email' => 'correct_email@example.com',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $user->refresh();
    expect($user->email)->toBe('correct_email@example.com')
        ->and($user->otp_code)->not->toBeNull()
        ->and($user->otp_expires_at)->not->toBeNull();

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\OtpMail::class, function ($mail) {
        return $mail->hasTo('correct_email@example.com');
    });
});

