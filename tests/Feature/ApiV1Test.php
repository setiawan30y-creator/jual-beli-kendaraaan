<?php

namespace Tests\Feature;

use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemo();
    }

    public function test_listing_index_uses_envelope_and_hides_sensitive_fields(): void
    {
        $res = $this->getJson('/api/v1/listings?kategori=motor&per_page=5')->assertOk()
            ->assertJsonStructure(['data' => [['id', 'slug', 'title', 'price', 'condition', 'cover_url']], 'meta' => ['total', 'current_page'], 'errors', 'request_id']);
        $this->assertTrue(collect($res->json('data'))->every(fn ($l) => $l['category'] === 'motor'));
        $json = json_encode($res->json());
        $this->assertStringNotContainsString('frame_number', $json);
        $this->assertStringNotContainsString('purchase_price', $json);
        $res->assertHeader('X-Request-Id');
    }

    public function test_login_token_and_me(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'pembeli@example.com', 'password' => 'salah', 'device_name' => 't'])
            ->assertStatus(422)->assertJsonPath('errors.0.field', 'email');

        $token = $this->postJson('/api/v1/auth/login', ['email' => 'pembeli@example.com', 'password' => 'password123', 'device_name' => 'flutter'])
            ->assertOk()->json('data.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', 'pembeli@example.com');
        $this->withToken($token)->getJson('/api/v1/me/favorites')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_validation_errors_use_consistent_envelope(): void
    {
        $listing = Listing::where('status', 'published')->first();
        $this->postJson('/api/v1/listings/'.$listing->slug.'/leads', [])
            ->assertStatus(422)
            ->assertJsonStructure(['data', 'meta', 'errors' => [['field', 'message']], 'request_id']);
    }

    public function test_unauthenticated_and_not_found_errors(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401)->assertJsonPath('errors.0.message', 'Tidak terautentikasi.');
        $this->getJson('/api/v1/listings/tidak-ada')->assertNotFound()->assertJsonStructure(['errors', 'request_id']);
    }

    public function test_booking_via_api_creates_lead(): void
    {
        $buyer = $this->user('pembeli@example.com');
        $listing = Listing::where('status', 'published')->whereNotNull('tenant_id')->first();
        $this->actingAs($buyer, 'sanctum')->postJson('/api/v1/bookings', [
            'listing_id' => $listing->id, 'booking_type' => 'test_ride', 'starts_at' => now()->addDays(2)->format('Y-m-d H:i'),
        ])->assertCreated()->assertJsonPath('data.status', 'menunggu');
        $this->assertDatabaseHas('leads', ['listing_id' => $listing->id, 'source' => 'booking']);
    }
}
