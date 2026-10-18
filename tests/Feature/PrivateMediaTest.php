<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\UnitMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PrivateMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemo();
    }

    public function test_private_document_upload_is_stored_privately_and_access_controlled(): void
    {
        $seller = $this->user('penjual@example.com');
        $listing = Listing::whereNull('tenant_id')->where('seller_user_id', $seller->id)->first();

        $this->actingAs($seller)->post(route('media.store', $listing->unit), [
            'category' => 'bpkb', 'file' => UploadedFile::fake()->image('bpkb.jpg', 800, 600), 'publish_consent' => 1,
        ])->assertRedirect();

        $media = UnitMedia::where('unit_id', $listing->unit_id)->where('category', 'bpkb')->firstOrFail();
        $this->assertSame('local', $media->disk);
        $this->assertFalse($media->publish_consent, 'Dokumen privat tidak boleh dapat izin publik.');
        $this->assertFalse($media->isPublic());
        $this->assertStringContainsString('/media/', $media->url());

        // Tamu → login; pengguna lain → 404; pemilik → 200 dengan header no-store
        $this->app['auth']->forgetGuards();
        $this->get(route('media.private', $media))->assertRedirect(route('login'));
        $this->actingAs($this->user('pembeli@example.com'))->get(route('media.private', $media))->assertNotFound();
        $this->actingAs($this->user('dealer@example.com'))->get(route('media.private', $media))->assertNotFound();
        $this->actingAs($seller)->get(route('media.private', $media))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->assertDatabaseHas('audit_logs', ['action' => 'media.private_viewed', 'resource_id' => $media->id]);

        // Tidak pernah ikut di galeri publik
        $this->assertFalse($listing->fresh()->publicPhotos()->contains('id', $media->id));
    }

    public function test_upload_rejects_non_image_and_oversized_files(): void
    {
        $seller = $this->user('penjual@example.com');
        $unit = Listing::whereNull('tenant_id')->where('seller_user_id', $seller->id)->first()->unit;

        $this->actingAs($seller)->post(route('media.store', $unit), ['category' => 'depan', 'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('file');
        $this->actingAs($seller)->post(route('media.store', $unit), ['category' => 'depan', 'file' => UploadedFile::fake()->image('big.jpg')->size(6000)])
            ->assertSessionHasErrors('file');
        $this->actingAs($seller)->post(route('media.store', $unit), ['category' => 'tidak_ada', 'file' => UploadedFile::fake()->image('a.jpg')])
            ->assertSessionHasErrors('category');
    }

    public function test_orang_unit_photo_is_never_public_automatically(): void
    {
        $seller = $this->user('penjual@example.com');
        $unit = Listing::whereNull('tenant_id')->where('seller_user_id', $seller->id)->first()->unit;
        $this->actingAs($seller)->post(route('media.store', $unit), ['category' => 'orang_unit', 'file' => UploadedFile::fake()->image('a.jpg'), 'publish_consent' => 1]);
        $m = UnitMedia::where('unit_id', $unit->id)->where('category', 'orang_unit')->firstOrFail();
        $this->assertFalse($m->isPublic());
    }
}
