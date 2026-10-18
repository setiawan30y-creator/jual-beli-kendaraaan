<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Lead;
use App\Models\Party;
use App\Models\PartyDocument;
use App\Support\Audit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Master Nasabah & Pemasok (tenant-scoped).
 * Dokumen identitas disimpan privat, nomor identitas dienkripsi & disamarkan,
 * setiap lihat/unggah/hapus/verifikasi dicatat di audit.
 */
class PartyController extends Controller
{
    public function __construct(protected TenantContext $ctx) {}

    public function index(Request $request)
    {
        $this->ctx->authorize('parties.view');
        $role = array_key_exists((string) $request->query('peran'), Party::ROLES) ? $request->query('peran') : null;
        $status = array_key_exists((string) $request->query('status'), Party::IDENTITY_STATUSES) ? $request->query('status') : null;
        $q = trim((string) $request->query('q'));

        $parties = $this->ctx->tenant()->parties()->with('city')
            ->when($role, fn ($b) => $b->whereJsonContains('roles', $role))
            ->when($status, fn ($b) => $b->where('identity_status', $status))
            ->when($q, function ($b) use ($q) {
                $term = '%'.str_replace(['%', '_'], '', $q).'%';
                $b->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('phone', 'like', $term)
                    ->orWhere('id_number_hash', Party::hashIdNumber($q)));
            })
            ->latest()->paginate(20)->withQueryString();

        return view('dealer.parties.index', compact('parties', 'role', 'status', 'q'));
    }

    public function create(Request $request)
    {
        $this->ctx->authorize('parties.manage');
        $party = new Party(['type' => 'perorangan', 'roles' => [$request->query('peran', 'customer')]]);

        return view('dealer.parties.form', ['party' => $party, 'cities' => City::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->ctx->authorize('parties.manage');
        $request->validate(['consent_ack' => ['accepted']], ['consent_ack.accepted' => 'Konfirmasi persetujuan nasabah atas pengumpulan data wajib dicentang.']);
        $data = $this->validated($request);

        if ($dupe = $this->findDuplicate($data)) {
            if (! $request->boolean('confirm_duplicate')) {
                return back()->withInput()->with('duplicate', $dupe->only(['id', 'name', 'phone']))
                    ->withErrors(['name' => 'Data dengan nomor identitas/telepon yang sama sudah ada: '.$dupe->name.'. Buka data tersebut, atau centang konfirmasi untuk tetap menyimpan.']);
            }
        }

        $party = DB::transaction(function () use ($data, $request) {
            $party = new Party(Arr::except($data, ['id_number']));
            $party->tenant_id = $this->ctx->id();
            $party->created_by = $request->user()->id;
            $party->setIdNumber($data['id_number'] ?? null);
            $party->save();
            Audit::log('party.created', $party, null, ['type' => $party->type, 'roles' => $party->roles]);
            $this->storeFormUploads($request, $party);

            return $party;
        });

        $uploaded = $party->documents()->count();

        return redirect()->route('dealer.parties.show', $party)->with('status', $uploaded
            ? 'Data tersimpan bersama '.$uploaded.' dokumen. Menunggu verifikasi identitas.'
            : 'Data tersimpan. Lengkapi dokumen identitas (KTP & selfie) di bawah.');
    }

    public function show(Party $party)
    {
        $this->authorizeParty($party, 'parties.view');
        $party->load(['city', 'documents', 'creator']);
        $leads = Lead::where('tenant_id', $this->ctx->id())
            ->where(fn ($q) => $q->where('party_id', $party->id)
                ->when($party->phone, fn ($w) => $w->orWhere('buyer_phone', $party->phone)))
            ->with('listing')->latest()->take(20)->get();

        return view('dealer.parties.show', [
            'party' => $party,
            'leads' => $leads,
            'canIdentity' => $this->ctx->can('parties.identity'),
            'canManage' => $this->ctx->can('parties.manage'),
            'canVerify' => $this->ctx->can('parties.verify'),
            'audit' => \App\Models\AuditLog::where('tenant_id', $this->ctx->id())->whereIn('resource_type', ['Party', 'PartyDocument'])
                ->where(fn ($q) => $q->where(fn ($w) => $w->where('resource_type', 'Party')->where('resource_id', $party->id))
                    ->orWhere(fn ($w) => $w->where('resource_type', 'PartyDocument')->whereIn('resource_id', $party->documents->pluck('id'))))
                ->with('actor')->latest('id')->take(15)->get(),
        ]);
    }

    public function edit(Party $party)
    {
        $this->authorizeParty($party, 'parties.manage');

        return view('dealer.parties.form', ['party' => $party, 'cities' => City::orderBy('name')->get()]);
    }

    public function update(Request $request, Party $party)
    {
        $this->authorizeParty($party, 'parties.manage');
        $data = $this->validated($request);
        $before = $party->only(['name', 'type', 'roles', 'phone', 'id_type']);

        $party->fill(Arr::except($data, ['id_number']));
        if ($request->filled('id_number')) {
            if (! $this->ctx->can('parties.identity') && $party->id_number_encrypted) {
                abort(403, 'Anda tidak berizin mengubah nomor identitas yang sudah tersimpan.');
            }
            $party->setIdNumber($data['id_number']);
            // Identitas berubah → perlu diverifikasi ulang
            if ($party->identity_status === 'terverifikasi') {
                $party->identity_status = 'menunggu_verifikasi';
            }
        }
        $party->save();
        Audit::log('party.updated', $party, $before, $party->only(array_keys($before)));
        $this->storeFormUploads($request, $party);

        return redirect()->route('dealer.parties.show', $party)->with('status', 'Data diperbarui.');
    }

    /** Ubah lead menjadi nasabah (Blueprint: hindari profil ganda). */
    public function fromLead(Request $request, Lead $lead)
    {
        $this->ctx->ensureOwns($lead);
        $this->ctx->authorize('parties.manage');
        abort_unless($this->ctx->can('leads.view_all') || $lead->assigned_user_id === $request->user()->id, 404);

        if ($lead->party_id) {
            return redirect()->route('dealer.parties.show', $lead->party_id);
        }

        $existing = $this->ctx->tenant()->parties()->where('phone', $lead->buyer_phone)->first();
        $party = DB::transaction(function () use ($lead, $existing, $request) {
            $party = $existing;
            if (! $party) {
                $party = new Party(['type' => 'perorangan', 'roles' => ['customer'], 'name' => $lead->buyer_name, 'phone' => $lead->buyer_phone]);
                $party->tenant_id = $this->ctx->id();
                $party->created_by = $request->user()->id;
                $party->source_lead_id = $lead->id;
                $party->save();
                Audit::log('party.created', $party, null, ['from_lead' => $lead->id]);
            } elseif (! $party->hasRole('customer')) {
                $party->roles = array_values(array_unique([...$party->roles, 'customer']));
                $party->save();
            }
            $lead->party_id = $party->id;
            $lead->save();
            $lead->activities()->create(['actor_user_id' => $request->user()->id, 'activity_type' => 'nasabah', 'notes' => 'Ditautkan ke data nasabah: '.$party->name]);

            return $party;
        });

        return redirect()->route('dealer.parties.show', $party)
            ->with('status', $existing ? 'Lead ditautkan ke nasabah yang sudah ada.' : 'Nasabah dibuat dari lead. Lengkapi identitasnya.');
    }

    public function uploadDocument(Request $request, Party $party)
    {
        $this->authorizeParty($party, 'parties.manage');
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(PartyDocument::CATEGORIES))],
            'file' => ['required', 'file', 'mimes:'.implode(',', config('marketplace.uploads.document_mimes')), 'max:'.config('marketplace.uploads.max_kb')],
        ], [], ['file' => 'berkas', 'category' => 'jenis dokumen']);

        if ($party->documents()->where('category', $data['category'])->count() >= config('marketplace.uploads.per_category')) {
            return back()->withErrors(['file' => 'Batas jumlah berkas untuk jenis dokumen ini sudah tercapai. Hapus berkas lama terlebih dahulu.']);
        }

        $this->storeDocument($party, $data['category'], $request->file('file'), $request->user()->id);

        return back()->with('status', PartyDocument::CATEGORIES[$data['category']]['label'].' berhasil diunggah.');
    }

    /** Unggahan langsung dari form tambah/ubah: KTP + selfie (perorangan) atau legalitas (perusahaan). */
    protected function storeFormUploads(Request $request, Party $party): void
    {
        $map = $party->type === 'perusahaan'
            ? ['doc_legalitas' => 'legalitas', 'doc_selfie' => 'foto_orang']
            : ['doc_ktp' => $party->id_type === 'sim' ? 'sim' : ($party->id_type === 'paspor' ? 'paspor' : 'ktp'), 'doc_selfie' => 'foto_orang'];
        foreach ($map as $input => $category) {
            if (! $request->hasFile($input)) {
                continue;
            }
            if ($party->documents()->where('category', $category)->count() >= config('marketplace.uploads.per_category')) {
                continue;
            }
            $this->storeDocument($party, $category, $request->file($input), $request->user()->id);
        }
    }

    protected function storeDocument(Party $party, string $category, \Illuminate\Http\UploadedFile $file, int $userId): PartyDocument
    {
        $path = $file->storeAs('tenants/'.$party->tenant_id.'/parties/'.$party->id.'/'.$category,
            Str::uuid().'.'.strtolower($file->guessExtension() ?: 'bin'), 'local');

        $doc = $party->documents()->create([
            'tenant_id' => $party->tenant_id, 'category' => $category, 'disk' => 'local', 'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200), 'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(), 'checksum' => hash_file('sha256', $file->getRealPath()), 'uploaded_by' => $userId,
        ]);

        if (in_array($category, $party->identityCategories(), true) && in_array($party->identity_status, ['belum_lengkap', 'ditolak', 'kedaluwarsa'], true)) {
            $party->identity_status = 'menunggu_verifikasi';
            $party->save();
        }
        Audit::log('party.document_uploaded', $doc, null, ['category' => $doc->category]);

        return $doc;
    }

    public function showDocument(PartyDocument $document)
    {
        $this->ctx->ensureOwns($document);
        $this->ctx->authorize('parties.identity');
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);
        Audit::log('party.document_viewed', $document, null, ['category' => $document->category]);

        $response = response()->file(Storage::disk($document->disk)->path($document->path), [
            'Content-Type' => $document->mime, 'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function deleteDocument(Request $request, PartyDocument $document)
    {
        $this->ctx->ensureOwns($document);
        $this->ctx->authorize('parties.manage');
        if ($document->verification_status === 'terverifikasi' && ! $request->boolean('confirm_verified')) {
            return back()->withErrors(['file' => 'Dokumen ini sudah terverifikasi. Konfirmasi untuk menghapus.']);
        }
        Storage::disk($document->disk)->delete($document->path);
        Audit::log('party.document_deleted', $document, ['category' => $document->category, 'status' => $document->verification_status], null);
        $document->delete();

        return back()->with('status', 'Dokumen dihapus.');
    }

    public function verify(Request $request, Party $party)
    {
        $this->authorizeParty($party, 'parties.verify');
        $data = $request->validate([
            'identity_status' => ['required', Rule::in(['terverifikasi', 'ditolak', 'kedaluwarsa', 'menunggu_verifikasi'])],
            'identity_note' => ['nullable', 'string', 'max:255', 'required_if:identity_status,ditolak'],
        ], [], ['identity_note' => 'catatan']);

        $party->load('documents');
        if ($data['identity_status'] === 'terverifikasi') {
            $hasIdDoc = $party->documents->whereIn('category', $party->identityCategories())->isNotEmpty();
            if (! $hasIdDoc || ! $party->id_number_encrypted) {
                return back()->withErrors(['identity_status' => 'Tidak dapat memverifikasi: nomor identitas dan dokumen identitas wajib ada.']);
            }
        }
        if ($party->created_by === $request->user()->id && $data['identity_status'] === 'terverifikasi' && $this->ctx->membership()->role !== 'tenant_owner') {
            return back()->withErrors(['identity_status' => 'Pemisahan tugas: data yang Anda input harus diverifikasi oleh petugas lain.']);
        }

        $before = $party->identity_status;
        $party->identity_status = $data['identity_status'];
        $party->identity_note = $data['identity_note'] ?? null;
        $party->identity_verified_by = $request->user()->id;
        $party->identity_verified_at = now();
        $party->save();
        if ($data['identity_status'] === 'terverifikasi') {
            $party->documents()->whereIn('category', $party->identityCategories())->update([
                'verification_status' => 'terverifikasi', 'verified_by' => $request->user()->id, 'verified_at' => now(),
            ]);
        }
        Audit::log('party.identity_verified', $party, ['identity_status' => $before], ['identity_status' => $party->identity_status], $party->identity_note);

        return back()->with('status', 'Status identitas: '.Party::IDENTITY_STATUSES[$party->identity_status].'.');
    }

    protected function findDuplicate(array $data): ?Party
    {
        $q = $this->ctx->tenant()->parties();
        $conds = [];
        if (! empty($data['id_number'])) {
            $conds['id_number_hash'] = Party::hashIdNumber($data['id_number']);
        }
        if (! empty($data['phone'])) {
            $conds['phone'] = $data['phone'];
        }
        if (! $conds) {
            return null;
        }

        return $q->where(function ($w) use ($conds) {
            foreach ($conds as $col => $val) {
                $w->orWhere($col, $val);
            }
        })->first();
    }

    protected function validated(Request $request): array
    {
        $type = $request->input('type') === 'perusahaan' ? 'perusahaan' : 'perorangan';
        if ($request->filled('id_number')) {
            $request->merge(['id_number' => Party::normalizeIdNumber((string) $request->input('id_number'))]);
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(['perorangan', 'perusahaan'])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(array_keys(Party::ROLES))],
            'name' => ['required', 'string', 'max:150'],
            'id_type' => ['nullable', Rule::in(array_keys(Party::ID_TYPES[$type])), 'required_with:id_number'],
            'id_number' => ['nullable', 'string', 'max:30',
                Rule::when($request->input('id_type') === 'ktp', ['regex:/^[0-9]{16}$/'])],
            'birth_place' => ['nullable', 'string', 'max:60'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'pic_name' => ['nullable', 'string', 'max:150'],
            'pic_phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'doc_ktp' => ['nullable', 'file', 'mimes:'.implode(',', config('marketplace.uploads.document_mimes')), 'max:'.config('marketplace.uploads.max_kb')],
            'doc_selfie' => ['nullable', 'file', 'mimes:'.implode(',', config('marketplace.uploads.mimes')), 'max:'.config('marketplace.uploads.max_kb')],
            'doc_legalitas' => ['nullable', 'file', 'mimes:'.implode(',', config('marketplace.uploads.document_mimes')), 'max:'.config('marketplace.uploads.max_kb')],
        ], ['id_number.regex' => 'NIK harus 16 digit angka.'], [
            'doc_ktp' => 'foto KTP/identitas', 'doc_selfie' => 'foto selfie', 'doc_legalitas' => 'dokumen legalitas',
            'name' => 'nama', 'roles' => 'peran', 'id_type' => 'jenis identitas', 'id_number' => 'nomor identitas',
            'birth_date' => 'tanggal lahir', 'pic_name' => 'nama perwakilan',
        ]);
        $data = Arr::except($data, ['doc_ktp', 'doc_selfie', 'doc_legalitas']);
        $data['roles'] = array_values(array_unique($data['roles']));
        if ($type === 'perusahaan') {
            $data['birth_place'] = null;
            $data['birth_date'] = null;
        }

        return $data;
    }

    protected function authorizeParty(Party $party, string $permission): void
    {
        $this->ctx->ensureOwns($party);
        $this->ctx->authorize($permission);
    }
}
