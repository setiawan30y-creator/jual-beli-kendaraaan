<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingPresenter;
use App\Models\Lead;
use App\Models\VehicleUnit;
use App\Services\PlanLimits;
use App\Support\ApiResponse;
use App\Support\Audit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Endpoint dealer: tenant berasal dari keanggotaan pengguna, bukan dari parameter client. */
class DealerController extends Controller
{
    public function __construct(protected TenantContext $ctx) {}

    public function inventory(Request $request)
    {
        $this->ctx->authorize('units.view');
        $page = $this->ctx->tenant()->units()->with(['vehicleModel.brand', 'variant', 'branch', 'listing'])
            ->when(array_key_exists((string) $request->query('status'), VehicleUnit::STATUSES), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()->paginate(min(max($request->integer('per_page', 20), 1), 50));
        $costs = $this->ctx->can('units.costs');

        return ApiResponse::paginated($page, fn ($u) => ListingPresenter::unit($u, $costs));
    }

    public function storeUnit(Request $request)
    {
        $this->ctx->authorize('units.manage');
        $data = $request->validate([
            'model_id' => ['required', 'exists:vehicle_models,id'],
            'branch_id' => ['nullable', Rule::exists('tenant_branches', 'id')->where('tenant_id', $this->ctx->id())],
            'condition' => ['required', Rule::in(['new', 'used'])],
            'year' => ['required', 'integer', 'between:1980,'.(now()->year + 1)],
            'color' => ['nullable', 'string', 'max:40'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'asking_price' => ['required', 'numeric', 'min:100000'],
            'frame_number' => ['nullable', 'string', 'max:40'],
            'ownership_type' => ['nullable', Rule::in(['owned', 'consignment'])],
        ]);
        $unit = new VehicleUnit(collect($data)->except('frame_number')->all() + ['ownership_type' => 'owned']);
        $unit->tenant_id = $this->ctx->id();
        $unit->status = 'inspection';
        $unit->setFrameNumber($data['frame_number'] ?? null);
        $unit->save();
        Audit::log('unit.created', $unit, null, ['via' => 'api']);

        return ApiResponse::ok(ListingPresenter::unit($unit->load('vehicleModel.brand'), $this->ctx->can('units.costs')), [], 201);
    }

    public function updateUnit(Request $request, VehicleUnit $unit)
    {
        $this->ctx->ensureOwns($unit);
        $this->ctx->authorize('units.manage');
        $data = $request->validate([
            'asking_price' => ['sometimes', 'numeric', 'min:100000'],
            'odometer_km' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'color' => ['sometimes', 'nullable', 'string', 'max:40'],
            'status' => ['sometimes', Rule::in(array_keys(VehicleUnit::STATUSES))],
        ]);
        $before = $unit->only(['asking_price', 'status']);
        if (isset($data['status']) && $data['status'] !== $unit->status) {
            abort_unless($unit->canTransitionTo($data['status']), 422, 'Perubahan status unit tidak diizinkan.');
            $unit->status = $data['status'];
        }
        $unit->fill(collect($data)->except('status')->all())->save();
        Audit::log('unit.updated', $unit, $before, $unit->only(['asking_price', 'status']));

        return ApiResponse::ok(ListingPresenter::unit($unit->load('vehicleModel.brand'), $this->ctx->can('units.costs')));
    }

    public function leads(Request $request)
    {
        $this->ctx->authorize('leads.manage');
        $q = $this->ctx->tenant()->leads()->with('listing');
        if (! $this->ctx->can('leads.view_all')) {
            $q->where('assigned_user_id', $request->user()->id);
        }

        return ApiResponse::paginated($q->latest()->paginate(20), fn ($l) => [
            'id' => $l->id, 'status' => $l->status, 'source' => $l->source, 'buyer_name' => $l->buyer_name,
            'buyer_phone' => $l->buyer_phone, 'message' => $l->message, 'listing' => $l->listing?->title,
            'assigned_user_id' => $l->assigned_user_id, 'next_follow_up_at' => $l->next_follow_up_at?->toIso8601String(),
            'created_at' => $l->created_at->toIso8601String(),
        ]);
    }

    public function updateLead(Request $request, Lead $lead)
    {
        $this->ctx->ensureOwns($lead);
        $this->ctx->authorize('leads.manage');
        abort_unless($this->ctx->can('leads.view_all') || $lead->assigned_user_id === $request->user()->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Lead::STATUSES))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $before = $lead->status;
        $lead->status = $data['status'];
        $lead->save();
        $lead->activities()->create(['actor_user_id' => $request->user()->id, 'activity_type' => 'perubahan', 'notes' => trim("Status: {$before} → {$lead->status}. ".($data['note'] ?? ''))]);
        Audit::log('lead.updated', $lead, ['status' => $before], ['status' => $lead->status]);

        return ApiResponse::ok(['id' => $lead->id, 'status' => $lead->status]);
    }

    public function subscription(PlanLimits $limits)
    {
        $tenant = $this->ctx->tenant();
        $sub = $tenant->activeSubscription?->load('plan');

        return ApiResponse::ok([
            'plan' => $sub?->plan?->name, 'status' => $sub?->status,
            'current_period_end' => $sub?->current_period_end?->toIso8601String(),
            'usage' => $limits->summary($tenant),
        ]);
    }
}
