<?php

namespace Tests\Feature;

use App\Mail\PasswordResetLinkMail;
use App\Models\PasswordResetToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $role = Role::query()->create(['code' => 'ADMIN', 'name' => 'Admin', 'is_active' => true]);

        return User::query()->create([
            'username' => 'bilal',
            'name' => 'Bilal',
            'email' => 'bilal@example.com',
            'password' => 'old-password-123',
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_forgot_page_loads(): void
    {
        $this->get('/password-recovery')->assertOk();
    }

    public function test_unknown_identifier_returns_generic_success(): void
    {
        Mail::fake();

        $this->post('/password-reset/request', ['identifier' => 'unknown-user'])
            ->assertSessionHas('success', 'If this account exists and has a valid email, reset instructions have been sent.');

        Mail::assertNothingSent();
    }

    public function test_known_identifier_sends_reset_email_and_does_not_show_token(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $response = $this->post('/password-reset/request', ['identifier' => $user->email]);

        $response->assertSessionHas('success', 'If this account exists and has a valid email, reset instructions have been sent.');
        $response->assertSessionMissing('reset_token');
        Mail::assertSent(PasswordResetLinkMail::class);
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_reset_form_loads_with_token_query(): void
    {
        $this->get('/password-recovery/reset?token=abc123')->assertOk()->assertSee('Reset Password');
    }

    public function test_valid_token_resets_password_and_token_cannot_be_reused(): void
    {
        $user = $this->makeUser();
        $plainToken = 'test-reset-token';

        $row = PasswordResetToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => Hash::make($plainToken),
            'expires_at' => now()->addMinutes(60),
        ]);

        $this->post('/password-reset/consume', [
            'token' => $plainToken,
            'new_password' => 'new-password-123',
            'new_password_confirmation' => 'new-password-123',
        ])->assertRedirect('/login');

        $user->refresh();
        $row->refresh();

        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertNotNull($row->used_at);

        $this->post('/password-reset/consume', [
            'token' => $plainToken,
            'new_password' => 'another-password-123',
            'new_password_confirmation' => 'another-password-123',
        ])->assertSessionHas('error', 'Invalid or expired reset link.');
    }

    public function test_invalid_or_expired_token_fails(): void
    {
        $this->post('/password-reset/consume', [
            'token' => 'invalid-token',
            'new_password' => 'new-password-123',
            'new_password_confirmation' => 'new-password-123',
        ])->assertSessionHas('error', 'Invalid or expired reset link.');
    }

    public function test_password_confirmation_is_required(): void
    {
        $this->post('/password-reset/consume', [
            'token' => 'abc123',
            'new_password' => 'new-password-123',
            'new_password_confirmation' => 'different-password',
        ])->assertSessionHasErrors(['new_password']);
    }
}
