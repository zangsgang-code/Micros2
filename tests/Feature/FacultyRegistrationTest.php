<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function facultyRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Faculty',
        'last_name' => 'Tester',
        'email' => 'faculty.tester@example.com',
        'username' => 'faculty.tester',
        'faculty_code' => 'INVALID',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms' => '1',
    ], $overrides);
}

test('faculty registration shows a validation error for an invalid faculty code', function () {
    $payload = facultyRegistrationPayload();

    $this->from(route('faculty.register'))
        ->followingRedirects()
        ->post(route('faculty.register.store'), $payload)
        ->assertOk()
        ->assertDontSee('<div class="alert-error">', false)
        ->assertSee('<p class="form-error">This Faculty Code Number is not valid. Please request one from the administrator.</p>', false);

    expect(User::where('email', $payload['email'])->exists())->toBeFalse();
});

test('faculty registration requires agreement to the terms and privacy policy', function () {
    $payload = facultyRegistrationPayload(['terms' => null]);

    $this->from(route('faculty.register'))
        ->post(route('faculty.register.store'), $payload)
        ->assertRedirect(route('faculty.register'))
        ->assertSessionHasErrors('terms');

    expect(User::where('email', $payload['email'])->exists())->toBeFalse();
});

test('faculty registration form displays the agreement checkbox and keeps sign up locked until checked', function () {
    $html = $this->get(route('faculty.register'))->assertOk()->getContent();

    expect($html)
        ->toContain('data-terms-checkbox')
        ->toContain('id="faculty-terms"')
        ->toContain('I agree to the')
        ->toContain('and have read and understood the')
        ->toContain('of UPSKILL.')
        ->toContain('href="'.route('terms').'"')
        ->toContain('href="'.route('privacy').'"')
        ->toMatch('/<button type="submit" class="register-submit" data-register-submit\s+disabled>/');
});
