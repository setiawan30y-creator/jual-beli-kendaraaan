<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Party;
use App\Models\PartyDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PartyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemo();
    }

    public function test_pages_render(): void
    {
        $owner = $this->user('dealer@example.com');
        $party = Party::where('name', 'Nasabah Demo Satu')->first();
        foreach ([route('dealer.parties.index'), route('dealer.parties.index', ['peran' => 'auction_house', 'q' => 'Lelang']),
            route('dealer.parties.create'), route('dealer.parties.show', $party), route('dealer.parties.edit', $party)] as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }
        $this->actingAs($owner)->get(route('dealer.parties.index', ['q' => '3174000000000001']))->assertSee('Nasabah Demo Satu');
    }

    public function test_create_customer_encrypts_and_masks_id_number(): void
    {
        $owner = $this->user('dealer@example.com');
        $this->actingAs($owner)->post(route('dealer.parties.store'), [
            'type' => 'perorangan', 'consent_ack' => 1, 'roles' => ['customer'], 'name' => 'Nasabah Baru', 'id_type' => 'ktp',
            'id_number' => '3201000000000002', 'phone' => '081311112222',
        ])->assertRedirect();

        $p = Party::where('name', 'Nasabah Baru')->firstOrFail();
        $this->assertSame('3201000000000002', $p->idNumber());
        $this->assertStringNotContainsString('3201000000000002', (string) $p->getRawOriginal('id_number_encrypted'));
        $this->assertSame('0002', $p->id_number_last4);
        $this->assertSame('belum_lengkap', $p->identity_status);

        // Sales hanya melihat versi tersamar
        $this->actingAs($this->user('sales@example.com'))->get(route('dealer.parties.show', $p))
            ->assertOk()->assertDontSee('3201000000000002')->assertSee('0002');
    }

    public function test_create_with_ktp_and_selfie_in_one_form(): void
    {
        $owner = $this->user('dealer@example.com');
        $this->actingAs($owner)->get(route('dealer.parties.create'))->assertOk()
            ->assertSee('name="doc_ktp"', false)->assertSee('name="doc_selfie"', false)->assertSee('multipart/form-data', false);

        $this->actingAs($owner)->post(route('dealer.parties.store'), [
            'type' => 'perorangan', 'roles' => ['customer'], 'name' => 'Tanpa Persetujuan',
        ])->assertSessionHasErrors('consent_ack');

        $this->actingAs($owner)->post(route('dealer.parties.store'), [
            'type' => 'perorangan', 'consent_ack' => 1, 'roles' => ['customer'], 'name' => 'Nasabah Lengkap', 'id_type' => 'ktp',
            'id_number' => '3201000000000009', 'phone' => '081300000009',
            'doc_ktp' => UploadedFile::fake()->image('ktp.jpg', 860, 540),
            'doc_selfie' => UploadedFile::fake()->image('selfie.jpg', 720, 960),
        ])->assertRedirect();

        $p = Party::where('name', 'Nasabah Lengkap')->firstOrFail();
        $this->assertEqualsCanonicalizing(['ktp', 'foto_orang'], $p->documents()->pluck('category')->all());
        $this->assertTrue($p->documents->every(fn ($d) => $d->disk === 'local'));
        $this->assertSame('menunggu_verifikasi', $p->identity_status);

        // Selfie hanya gambar; PDF ditolak
        $this->actingAs($owner)->post(route('dealer.parties.store'), [
            'type' => 'perorangan', 'consent_ack' => 1, 'roles' => ['customer'], 'name' => 'Selfie PDF',
            'doc_selfie' => UploadedFile::fake()->create('s.pdf', 50, 'application/pdf'),
        ])->assertSessionHasErrors('doc_selfie');

        // Upload tambahan lewat form ubah
        $this->actingAs($owner)->put(route('dealer.parties.update', $p), [
            'type' => 'perorangan', 'roles' => ['customer'], 'name' => 'Nasabah Lengkap',
            'doc_selfie' => UploadedFile::fake()->image('selfie2.jpg'),
        ])->assertRedirect();
        $this->assertSame(2, $p->documents()->where('category', 'foto_orang')->count());
    }

    public function test_ktp_number_must_be_16_digits_and_duplicates_are_flagged(): void
    {
        $owner = $this->user('dealer@example.com');
        $this->actingAs($owner)->post(route('dealer.parties.store'), [
            'type' => 'perorangan', 'consent_ack' => 1, 'roles' => ['customer'], 'name' => 'X', 'id_type' => 'ktp', 'id_number' => '123',
        ])->assertSessionHasErrors('id_number');

        $this->actingAs($owner)->post(route('dealer.parties.store'), [
            'type' => 'perorangan', 'consent_ack' => 1, 'roles' => ['customer'], 'name' => 'Duplikat', 'id_type' => 'ktp', 'id_number' => '3174 0000 0000 0001',
        ])->assertSessionHasErrors('name')->assertSessionHas('duplicate');
        $this->assertFalse(Party::where('name', 'Duplikat')->exists());

        $this->actingAs($owner)->post(route('dealer.parties.store'), [
            'type' => 'perorangan', 'consent_ack' => 1, 'roles' => ['customer'], 'name' => 'Duplikat', 'id_type' => 'ktp', 'id_number' => '3174000000000001', 'confirm_duplicate' => 1,
        ])->assertRedirect();
        $this->assertTrue(Party::where('name', 'Duplikat')->exists());
    }

    public function test_documents_are_private_and_access_controlled(): void
    {
        $owner = $this->user('dealer@example.com');
        $party = Party::where('name', 'Nasabah Demo Dua')->first();

        $this->actingAs($this->user('sales@example.com'))->post(route('dealer.parties.documents.store', $party), [
            'category' => 'ktp', 'file' => UploadedFile::fake()->image('ktp.jpg', 800, 500),
        ])->assertRedirect();
        $doc = PartyDocument::where('party_id', $party->id)->where('category', 'ktp')->firstOrFail();
        $this->assertSame('local', $doc->disk);
        $this->assertSame('menunggu_verifikasi', $party->fresh()->identity_status);

        // Sales boleh unggah tapi tidak boleh membuka; owner boleh dan tercatat di audit
        $this->actingAs($this->user('sales@example.com'))->get(route('dealer.parties.documents.show', $doc))->assertForbidden();
        $this->actingAs($owner)->get(route('dealer.parties.documents.show', $doc))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->assertDatabaseHas('audit_logs', ['action' => 'party.document_viewed', 'resource_id' => $doc->id]);

        // Tenant lain → 404
        $other = $this->user('showroom@example.com');
        $this->actingAs($other)->get(route('dealer.parties.documents.show', $doc))->assertNotFound();
        $this->actingAs($other)->get(route('dealer.parties.show', $party))->assertNotFound();
        $this->actingAs($other)->delete(route('dealer.parties.documents.destroy', $doc))->assertNotFound();

        // Tamu → login
        $this->app['auth']->forgetGuards();
        $this->get(route('dealer.parties.documents.show', $doc))->assertRedirect(route('login'));

        $this->actingAs($owner)->post(route('dealer.parties.documents.store', $party), [
            'category' => 'ktp', 'file' => UploadedFile::fake()->create('a.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('file');
    }

    public function test_verification_requires_document_and_id_number(): void
    {
        $owner = $this->user('dealer@example.com');
        $party = Party::where('name', 'Nasabah Demo Satu')->first(); // ada NIK, belum ada dokumen
        $this->actingAs($owner)->post(route('dealer.parties.verify', $party), ['identity_status' => 'terverifikasi'])->assertSessionHasErrors('identity_status');

        $this->actingAs($owner)->post(route('dealer.parties.documents.store', $party), ['category' => 'ktp', 'file' => UploadedFile::fake()->image('k.jpg')]);
        $this->actingAs($owner)->post(route('dealer.parties.verify', $party), ['identity_status' => 'ditolak'])->assertSessionHasErrors('identity_note');
        $this->actingAs($owner)->post(route('dealer.parties.verify', $party), ['identity_status' => 'terverifikasi'])->assertSessionHasNoErrors();
        $this->assertSame('terverifikasi', $party->fresh()->identity_status);
        $this->assertSame('terverifikasi', $party->documents()->first()->verification_status);
        $this->actingAs($this->user('sales@example.com'))->post(route('dealer.parties.verify', $party), ['identity_status' => 'ditolak', 'identity_note' => 'x'])->assertForbidden();
    }

    public function test_lead_can_be_converted_to_customer_once(): void
    {
        $owner = $this->user('dealer@example.com');
        $tenantId = $this->tenant('dealer-motor-contoh')->id;
        $lead = Lead::where('tenant_id', $tenantId)->first()->replicate(['party_id']);
        $lead->party_id = null;
        $lead->buyer_name = 'Calon Pembeli Baru';
        $lead->buyer_phone = '081377778888';
        $lead->save();

        $this->actingAs($owner)->get(route('dealer.leads.show', $lead))->assertSee('Jadikan nasabah');
        $this->actingAs($owner)->post(route('dealer.leads.to-party', $lead))->assertRedirect();
        $party = Party::where('phone', '081377778888')->firstOrFail();
        $this->assertTrue($party->hasRole('customer'));
        $this->assertSame($party->id, $lead->fresh()->party_id);

        $this->actingAs($owner)->post(route('dealer.leads.to-party', $lead));
        $this->assertSame(1, Party::where('phone', '081377778888')->count());

        $this->actingAs($this->user('showroom@example.com'))->post(route('dealer.leads.to-party', $lead))->assertNotFound();
    }
}
