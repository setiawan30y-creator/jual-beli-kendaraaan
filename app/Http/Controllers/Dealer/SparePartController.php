<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Party;
use App\Models\SparePart;
use App\Services\InventoryService;
use App\Support\Audit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SparePartController extends Controller
{
    public function __construct(protected TenantContext $ctx) {}

    public function index(Request $request)
    {
        $this->ctx->authorize('parts.view');
        $q = trim((string) $request->query('q'));
        $low = $request->boolean('menipis');
        $cat = array_key_exists((string) $request->query('kategori'), SparePart::CATEGORIES) ? $request->query('kategori') : null;

        $base = SparePart::where('tenant_id', $this->ctx->id());
        $parts = (clone $base)
            ->when($q, fn ($b) => $b->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
            ->when($cat, fn ($b) => $b->where('category', $cat))
            ->when($low, fn ($b) => $b->whereColumn('qty_on_hand', '<=', 'min_stock'))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('dealer.parts.index', [
            'parts' => $parts, 'q' => $q, 'low' => $low, 'cat' => $cat,
            'totalValue' => (float) (clone $base)->selectRaw('COALESCE(SUM(qty_on_hand * avg_cost), 0) as v')->value('v'),
            'lowCount' => (clone $base)->whereColumn('qty_on_hand', '<=', 'min_stock')->where('status', 'active')->count(),
            'canManage' => $this->ctx->can('parts.manage'),
        ]);
    }

    public function store(Request $request)
    {
        $this->ctx->authorize('parts.manage');
        $data = $this->validated($request);
        $part = new SparePart($data);
        $part->tenant_id = $this->ctx->id();
        $part->save();
        Audit::log('part.created', $part, null, ['sku' => $part->sku]);

        return redirect()->route('dealer.parts.show', $part)->with('status', 'Sparepart ditambahkan. Catat penerimaan barang untuk mengisi stok.');
    }

    public function show(SparePart $part)
    {
        $this->ctx->ensureOwns($part);
        $this->ctx->authorize('parts.view');

        return view('dealer.parts.show', [
            'part' => $part,
            'movements' => $part->movements()->with(['unit.vehicleModel.brand', 'supplier', 'creator'])->latest('id')->paginate(30),
            'suppliers' => $this->ctx->tenant()->parties()->whereJsonContains('roles', 'supplier')->orderBy('name')->get(),
            'canManage' => $this->ctx->can('parts.manage'),
        ]);
    }

    public function update(Request $request, SparePart $part)
    {
        $this->ctx->ensureOwns($part);
        $this->ctx->authorize('parts.manage');
        $data = $this->validated($request, $part);
        $before = $part->only(array_keys($data));
        $part->update($data);
        Audit::log('part.updated', $part, $before, $part->only(array_keys($data)));

        return back()->with('status', 'Data sparepart diperbarui.');
    }

    public function receive(Request $request, SparePart $part, InventoryService $inv)
    {
        $this->ctx->ensureOwns($part);
        $this->ctx->authorize('parts.manage');
        $data = $request->validate([
            'qty' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'party_id' => ['nullable', Rule::exists('parties', 'id')->where('tenant_id', $this->ctx->id())],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['qty' => 'jumlah', 'unit_cost' => 'harga satuan', 'party_id' => 'pemasok']);

        $inv->receive($part, (float) $data['qty'], (float) $data['unit_cost'],
            isset($data['party_id']) ? Party::find($data['party_id']) : null, $data['reference_no'] ?? null, $data['note'] ?? null);

        return back()->with('status', 'Stok '.$part->name.' bertambah '.SparePart::qty($data['qty']).' '.$part->uom.'.');
    }

    public function adjust(Request $request, SparePart $part, InventoryService $inv)
    {
        $this->ctx->ensureOwns($part);
        $this->ctx->authorize('parts.manage');
        $data = $request->validate([
            'counted_qty' => ['required', 'numeric', 'min:0', 'max:100000'],
            'reason' => ['required', 'string', 'max:255'],
        ], [], ['counted_qty' => 'jumlah fisik', 'reason' => 'alasan']);
        $m = $inv->adjust($part, (float) $data['counted_qty'], $data['reason']);

        return back()->with('status', $m ? 'Stok disesuaikan ('.InventoryMovement::TYPES[$m->type]['label'].' '.SparePart::qty($m->qty).').' : 'Jumlah fisik sama dengan sistem; tidak ada penyesuaian.');
    }

    protected function validated(Request $request, ?SparePart $part = null): array
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-_.\/]+$/',
                Rule::unique('spare_parts', 'sku')->where('tenant_id', $this->ctx->id())->ignore($part?->id)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', Rule::in(array_keys(SparePart::CATEGORIES))],
            'uom' => ['required', 'string', 'max:12'],
            'compatible_with' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:60'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ], ['sku.regex' => 'Kode SKU hanya boleh huruf, angka, dan - _ . /'], ['sku' => 'kode SKU', 'name' => 'nama sparepart', 'uom' => 'satuan', 'min_stock' => 'stok minimum']);
        $data['sku'] = strtoupper($data['sku']);
        $data['min_stock'] = $data['min_stock'] ?? 0;
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }
}
