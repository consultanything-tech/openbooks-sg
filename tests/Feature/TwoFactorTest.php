<?php

namespace Tests\Feature;

use App\Models\Company;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create();
    }

    public function test_2fa_setup_page_loads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/settings/two-factor');
        $response->assertStatus(200);
    }

    public function test_2fa_enable_returns_json(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/settings/two-factor/enable');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'secret',
            'otpauth_url',
        ]);

        // The TOTP secret must never be sent to a third-party QR service.
        $response->assertJsonMissingPath('qr_url');
    }

    public function test_2fa_challenge_page_loads(): void
    {
        $user = $this->actingAsAdmin();

        $response = $this->withSession(['2fa_user_id' => $user->id])
            ->get('/2fa/challenge');

        $response->assertStatus(200);
    }

    public function test_2fa_challenge_redirects_without_session(): void
    {
        $response = $this->get('/2fa/challenge');
        $response->assertRedirect(route('login'));
    }
}
