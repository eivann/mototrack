<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the privacy policy page is public, returns 200 and describes data privacy compliance', function () {
    $response = $this->get('/privacy-policy');

    $response->assertOk();
    $html = $response->getContent();

    expect($html)
        ->toContain('Privacy Policy')
        ->toContain('Edmar Motorcycle Parts')
        ->toContain('Republic Act No. 10173')
        ->toContain('Data Privacy Act of 2012')
        ->toContain('MotoTrack');
});

test('welcome page contains footer links to privacy policy and terms', function () {
    $response = $this->get('/');

    $response->assertOk();
    $html = $response->getContent();

    expect($html)
        ->toContain(route('privacy-policy'))
        ->toContain(route('terms'))
        ->toContain('Privacy Policy')
        ->toContain('Terms and Conditions');
});

