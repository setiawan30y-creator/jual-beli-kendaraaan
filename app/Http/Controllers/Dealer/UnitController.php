<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\UnitMedia;
use App\Models\VehicleCostEntry;
use App\Models\VehicleModel;
use App\Models\VehicleUnit;
use App\Services\MediaService;
use App\Support\Audit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function __construct(protected TenantContext $ctx) {}

    public function index(Request $request)
    {
        $this->ctx->authorize('units.view');
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));
        $units = $this->ctx->tenant()->units()
            ->with(['vehicleModel.brand', 'variant', 'branch', 'listing', 'media'])
            ->when(array_key_exists((string) $status, VehicleUnit::STATUSES), fn ($b) => $b->where('status', $status))
            ->when($q, fn ($b) => $b->where(fn ($w) => $w->where('stock_code', 'like', "%{$q}%")
                ->orWhereHas('vehicleModel', fn ($m) => $m->where('name', 'like', "%{$q}%"))))
            ->latest()->paginate(15)->withQueryString();

        return view('dealer.units.index', compact('units', 'status', 'q'));
    }

    public function create()
    {
        $this->ctx->authorize('units.manage');

        return view('dealer.units.form', $this->formData(new VehicleUnit(['condition' => 'used', 'year' => now()->year])));
    }

    public function store(Request $request)
    {
        $this->ctx->authorize('units.manage');
        $data = $this->validated($request);

        $unit = DB::transaction(function () use ($data, $request) {
            $unit = new VehicleUnit(collect($data)->except(['frame_number', 'engine_number', 'purchase_price'])->all());
            $unit->tenant_id = $this->ctx->id();
            $unit->status = 'inspection';
            if ($this->ctx->can('units.costs')) {
                $unit->purchase_price = $data['purchase_price'] ?? null;
            }
            $unit->setFrameNumber($data['frame_number'] ?? null);
            $unit->setEngineNumber($data['engine_number'] ?? null);
            $unit->save();

            if ($unit->purchase_price) {
                $this->postCost($unit, 'pembelian', 'Harga beli unit', (float) $unit->purchase_price, 'posted', $unit->acquired_at?->toDateString());
            }
            Audit::log('unit.created', $unit, null, ['status' => $unit->status, 'asking_price' => $unit->asking_price]);

            return $unit;
        });

        return redirect()->route('dealer.units.show', $unit)->with('status', 'Unit tersimpan. Unggah foto & bukti per kategori di bawah.');
    }

    public function show(VehicleUnit $unit, MediaService $media)
    {
        $this->ctx->ensureOwns($unit);
        $this->ctx->authorize('units.view');
        $unit->load(['vehicleModel.brand', 'variant', 'branch', 'media', 'costs.creator', 'listing']);

        return view('dealer.units.show', [
            'unit' => $unit,
            'checklist' => $media->checklist($unit),
            'summary' => $unit->costSummary(),
            'canCosts' => $this->ctx->can('units.costs'),
            'canManage' => $this->ctx->can('units.manage'),
            'canPrivate' => $this->ctx->can('media.private'),
            'canVerify' => $this->ctx->can('media.verify'),
            'audit' => \App\Models\AuditLog::where('tenant_id', $this->ctx->id())->where('resource_type', 'VehicleUnit')->where('resource_id', $unit->id)->with('actor')->latest('id')->take(10)->get(),
        ]);
    }

    public function edit(VehicleUnit $unit)
    {
        $this->ctx->ensureOwns($unit);
        $this->ctx->authorize('units.manage');

        return view('dealer.units.form', $this->formData($unit));
    }

    public function update(Request $request, VehicleUnit $unit)
    {
        $this->ctx->ensureOwns($unit);
        $this->ctx->authorize('units.manage');
        $data = $this->validated($request);
        $before = $unit->only(['asking_price', 'odometer_km', 'year', 'branch_id']);

        $unit->fill(collect($data)->except(['frame_number', 'engine_number', 'purchase_price'])->all());
        if ($request->filled('frame_number')) {
            $unit->setFrameNumber($data['frame_number']);
        }
        if ($request->filled('engine_number')) {
            $unit->setEngineNumber($data['engine_number']);
        }
        $unit->save();
        Audit::log('unit.updated', $unit, $before, $unit->only(array_keys($before)));

        // Harga iklan draf mengikuti harga unit; iklan tayang diubah lewat menu iklan.
        $unit->listing()->where('status', 'draft')->update(['price' => $unit->asking_price]);

        return redirect()->route('dealer.units.show', $unit)->with('status', 'Unit diperbarui.');
    }

    public function status(Request $request, VehicleUnit $unit)
    {
        $this->ctx->ensureOwns($unit);
        $this->ctx->authorize('units.manage');
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(VehicleUnit::STATUSES))],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($unit, $data) {
            $locked = VehicleUnit::whereKey($unit->id)->lockForUpdate()->first();
            if (! $locked->canTransitionTo($data['status'])) {
                abort(422, 'Perubahan status unit dari '.VehicleUnit::STATUSES[$locked->status].' ke '.VehicleUnit::STATUSES[$data['status']].' tidak diizinkan.');
            }
            $from = $locked->status;
            $locked->status = $data['status'];
            $locked->save();
            Audit::log('unit.status_changed', $locked, ['status' => $from], ['status' => $data['status']], $data['reason'] ?? null);

            // Sinkronisasi status unit ↔ iklan
            $listing = $locked->listing;
            if ($listing) {
                $map = ['reserved' => 'reserved', 'sold' => 'sold'];
                if (isset($map[$data['status']]) && in_array($listing->status, ['published', 'reserved'], true) && $listing->status !== $map[$data['status']]) {
                    app(\App\Services\ListingService::class)->transition($listing, $map[$data['status']], 'Sinkron status unit');
                } elseif ($data['status'] === 'available' && $listing->status === 'reserved') {
                    app(\App\Services\ListingService::class)->transition($listing, 'published', 'Sinkron status unit');
                } elseif (in_array($data['status'], ['withdrawn', 'maintenance', 'inspection'], true) && in_array($listing->status, ['published', 'pending_review'], true)) {
                    $listing->status === 'pending_review'
                        ? app(\App\Services\ListingService::class)->transition($listing, 'draft', 'Unit tidak tersedia')
                        : app(\App\Services\ListingService::class)->transition($listing, 'archived', 'Unit tidak tersedia');
                }
            }
        });

        return back()->with('status', 'Status unit diperbarui.');
    }

    public function addCost(Request $request, VehicleUnit $unit)
    {
        $this->ctx->ensureOwns($unit);
        $this->ctx->authorize('units.costs');
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(VehicleCostEntry::CATEGORIES))],
            'description' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:1', 'max:100000000000'],
            'status' => ['required', Rule::in(['estimate', 'posted'])],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ], [], ['amount' => 'nilai', 'description' => 'keterangan']);

        // Cegah posting ganda akibat klik berulang (Blueprint: idempotency key).
        $dupe = \Illuminate\Support\Facades\Cache::add('cost:'.$this->ctx->id().':'.$data['idempotency_key'], 1, now()->addHour());
        if (! $dupe) {
            return back()->with('status', 'Biaya ini sudah tercatat.');
        }
        $this->postCost($unit, $data['category'], $data['description'], (float) $data['amount'], $data['status'], $data['entry_date'], $data['reference_no'] ?? null);

        return back()->with('status', $data['status'] === 'posted' ? 'Biaya diposting ke HPP unit.' : 'Estimasi biaya dicatat (belum masuk HPP).');
    }

    /** Biaya yang sudah diposting tidak dihapus — dibalik dengan entri reversal (Blueprint: koreksi yang dapat diaudit). */
    public function reverseCost(Request $request, VehicleUnit $unit, VehicleCostEntry $cost)
    {
        $this->ctx->ensureOwns($unit);
        $this->ctx->authorize('units.costs');
        abort_unless($cost->unit_id === $unit->id && $cost->tenant_id === $this->ctx->id(), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'alasan']);
        abort_if($cost->status === 'reversed' || $cost->reverses_id, 422, 'Entri ini tidak dapat dibalik.');

        DB::transaction(function () use ($unit, $cost, $data) {
            if ($cost->status === 'posted') {
                $rev = $this->postCost($unit, $cost->category, 'Reversal: '.$cost->description, -1 * (float) $cost->amount, 'posted', now()->toDateString(), $cost->reference_no);
                $rev->reverses_id = $cost->id;
                $rev->save();
            }
            $cost->status = 'reversed';
            $cost->save();
            Audit::log('cost.reversed', $cost, ['status' => 'posted'], ['status' => 'reversed'], $data['reason']);
        });

        return back()->with('status', 'Entri biaya dibalik.');
    }

    public function verifyMedia(Request $request, UnitMedia $media)
    {
        $this->ctx->ensureOwns($media);
        $this->ctx->authorize('media.verify');
        $data = $request->validate(['verification_status' => ['required', Rule::in(['terverifikasi', 'perlu_perbaikan'])]]);
        $before = $media->verification_status;
        $media->verification_status = $data['verification_status'];
        $media->verified_by = $request->user()->id;
        $media->verified_at = now();
        $media->save();
        Audit::log('media.verified', $media, ['status' => $before], ['status' => $media->verification_status]);

        return back()->with('status', 'Status verifikasi diperbarui.');
    }

    protected function postCost(VehicleUnit $unit, string $category, string $description, float $amount, string $status, ?string $date = null, ?string $ref = null): VehicleCostEntry
    {
        $entry = new VehicleCostEntry([
            'unit_id' => $unit->id, 'category' => $category, 'description' => $description, 'amount' => $amount,
            'status' => $status, 'entry_date' => $date ?? now()->toDateString(), 'reference_no' => $ref, 'created_by' => auth()->id(),
        ]);
        $entry->tenant_id = $unit->tenant_id;
        $entry->save();
        Audit::log('cost.'.$status, $entry, null, ['category' => $category, 'amount' => $amount]);

        return $entry;
    }

    protected function validated(Request $request): array
    {
        $tenantId = $this->ctx->id();

        return $request->validate([
            'model_id' => ['required', 'exists:vehicle_models,id'],
            'variant_id' => ['nullable', 'exists:vehicle_variants,id'],
            'branch_id' => ['nullable', Rule::exists('tenant_branches', 'id')->where('tenant_id', $tenantId)],
            'stock_code' => ['nullable', 'string', 'max:40'],
            'condition' => ['required', Rule::in(['new', 'used'])],
            'year' => ['required', 'integer', 'between:1980,'.(now()->year + 1)],
            'color' => ['nullable', 'string', 'max:40'],
            'odometer_km' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'frame_number' => ['nullable', 'string', 'max:40'],
            'engine_number' => ['nullable', 'string', 'max:40'],
            'plate_region' => ['nullable', 'string', 'max:10'],
            'registration_status' => ['nullable', Rule::in(['lengkap', 'stnk_hidup', 'stnk_mati', 'proses', 'baru'])],
            'ownership_type' => ['required', Rule::in(['owned', 'consignment'])],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:100000000000'],
            'asking_price' => ['required', 'numeric', 'min:100000', 'max:100000000000'],
            'acquired_at' => ['nullable', 'date', 'before_or_equal:today'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['model_id' => 'model', 'asking_price' => 'harga jual', 'purchase_price' => 'harga beli']);
    }

    protected function formData(VehicleUnit $unit): array
    {
        return [
            'unit' => $unit,
            'models' => VehicleModel::with(['brand', 'variants'])->get()->sortBy(fn ($m) => $m->category.$m->fullName())->values(),
            'branches' => $this->ctx->tenant()->branches()->where('status', 'active')->get(),
            'canCosts' => $this->ctx->can('units.costs'),
        ];
    }
}
