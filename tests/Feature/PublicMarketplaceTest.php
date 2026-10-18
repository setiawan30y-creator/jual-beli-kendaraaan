<?php

namespace Tests\Feature;

use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemo();
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/kendaraan', '/motor', '/motor/baru', '/motor/seken', '/mobil', '/dealer', '/dealer/dealer-motor-contoh',
            '/simulasi-kredit', '/panduan', '/pusat-bantuan', '/kebijakan-privasi', '/syarat-ketentuan', '/offline', '/jual'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
    }

    public function test_home_has_pwa_install_entry_and_manifest(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('data-install-trigger', false)
            ->assertSee('Instal Aplikasi');
    }

    public function test_catalog_filters_by_category_and_price(): void
    {
        $res = $this->get('/kendaraan?kategori=mobil&harga_max=200000000&urut=termurah')->assertOk();
        $res->assertSee('Toyota Avanza 2021')->assertSee('Honda Brio')->assertDontSee('Honda Vario 160 2023');
    }

    public function test_published_listing_is_visible_but_pending_is_not(): void
    {
        $published = Listing::where('status', 'published')->first();
        $pending = Listing::where('status', 'pending_review')->first();

        $this->get(route('listings.show', $published))->assertOk()->assertSee($published->title)
            ->assertDontSee('MH1DEMO'); // nomor rangka tidak pernah tampil publik
        $this->get(route('listings.show', $pending))->assertNotFound();
        $this->getJson('/api/v1/listings/'.$pending->slug)->assertNotFound();
    }

    public function test_guest_can_send_lead_with_consent(): void
    {
        $listing = Listing::where('status', 'published')->whereNotNull('tenant_id')->first();
        $this->post(route('listings.lead', $listing), ['buyer_name' => 'Calon Pembeli', 'buyer_phone' => '081234567890', 'source' => 'form'])
            ->assertSessionHasErrors('contact_consent');

        $this->post(route('listings.lead', $listing), [
            'buyer_name' => 'Calon Pembeli', 'buyer_phone' => '081234567890', 'contact_consent' => '1', 'source' => 'form',
        ])->assertRedirect();
        $this->assertDatabaseHas('leads', ['listing_id' => $listing->id, 'tenant_id' => $listing->tenant_id, 'buyer_name' => 'Calon Pembeli']);
    }

    public function test_report_listing_creates_report_and_audit(): void
    {
        $listing = Listing::where('status', 'published')->first();
        $this->post(route('listings.report', $listing), ['reason' => 'penipuan', 'description' => 'Harga tidak wajar'])->assertRedirect();
        $this->assertDatabaseHas('listing_reports', ['listing_id' => $listing->id, 'reason' => 'penipuan']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'listing.reported']);
    }

    public function test_guest_public_pages_are_marked_offline_cacheable_but_private_pages_are_not(): void
    {
        $this->get('/')->assertHeader('X-PWA-Cacheable', '1');
        $this->actingAs($this->user('pembeli@example.com'))->get('/')->assertHeaderMissing('X-PWA-Cacheable');
        $this->actingAs($this->user('pembeli@example.com'))->get('/akun')->assertHeaderMissing('X-PWA-Cacheable');
    }
}
