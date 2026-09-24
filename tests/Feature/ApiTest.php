<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiTest extends TestCase
{
    private User $apiUser;
    private ApiToken $apiToken;
    private string $plainToken;

    protected function setUp(): void
    {
        parent::setUp();

        Company::factory()->create();

        $this->apiUser = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $result = ApiToken::generateFor(
            $this->apiUser,
            'Test API Token',
            now()->addYear(),
        );
        $this->apiToken = $result['model'];
        $this->plainToken = $result['plain_text_token'];
    }

    private function withApiToken(): static
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->plainToken);
    }

    public function test_api_requires_token(): void
    {
        $response = $this->getJson('/api/v1/invoices');
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_api_returns_invoices_with_valid_token(): void
    {
        $response = $this->withApiToken()->getJson('/api/v1/invoices');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_api_returns_customers(): void
    {
        $response = $this->withApiToken()->getJson('/api/v1/customers');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_api_returns_company(): void
    {
        $response = $this->withApiToken()->getJson('/api/v1/company');
        $response->assertStatus(200);
    }
}
