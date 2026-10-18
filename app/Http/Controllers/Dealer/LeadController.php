<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Support\Audit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function __construct(protected TenantContext $ctx) {}

    public function index(Request $request)
    {
        $this->ctx->authorize('leads.manage');
        $status = $request->query('status');
        $leads = $this->scoped()
            ->with(['listing', 'assignee'])
            ->when(array_key_exists((string) $status, Lead::STATUSES), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(20)->withQueryString();
        $counts = $this->scoped()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('dealer.leads.index', compact('leads', 'status', 'counts'));
    }

    public function show(Lead $lead)
    {
        $this->authorizeLead($lead);
        $lead->load(['listing.unit.vehicleModel.brand', 'activities.actor', 'assignee']);
        $staff = $this->ctx->tenant()->memberships()->where('status', 'active')
            ->whereIn('role', ['tenant_owner', 'tenant_admin', 'branch_manager', 'sales'])->with('user')->get();

        return view('dealer.leads.show', ['lead' => $lead, 'staff' => $staff, 'canAssign' => $this->ctx->can('leads.view_all')]);
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $tenantId = $this->ctx->id();
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Lead::STATUSES))],
            'assigned_user_id' => ['nullable', Rule::exists('tenant_memberships', 'user_id')->where('tenant_id', $tenantId)->where('status', 'active')],
            'next_follow_up_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $before = $lead->only(['status', 'assigned_user_id']);
        $lead->status = $data['status'];
        $lead->next_follow_up_at = $data['next_follow_up_at'] ?? null;
        if ($this->ctx->can('leads.view_all') && $request->has('assigned_user_id')) {
            $lead->assigned_user_id = $data['assigned_user_id'] ?? null;
        }
        $lead->save();

        $changes = [];
        if ($before['status'] !== $lead->status) {
            $changes[] = 'Status: '.Lead::STATUSES[$before['status']].' → '.Lead::STATUSES[$lead->status];
        }
        if ($before['assigned_user_id'] !== $lead->assigned_user_id) {
            $changes[] = 'Penugasan sales diperbarui';
        }
        if ($changes || ! empty($data['note'])) {
            $lead->activities()->create([
                'actor_user_id' => $request->user()->id,
                'activity_type' => $changes ? 'perubahan' : 'catatan',
                'notes' => trim(implode('. ', $changes).(! empty($data['note']) ? "\n".$data['note'] : '')),
            ]);
        }
        Audit::log('lead.updated', $lead, $before, $lead->only(['status', 'assigned_user_id']));

        return back()->with('status', 'Lead diperbarui.');
    }

    /** Sales hanya melihat lead yang ditugaskan kepadanya (Blueprint §3). */
    protected function scoped()
    {
        $q = $this->ctx->tenant()->leads();
        if (! $this->ctx->can('leads.view_all')) {
            $q->where('assigned_user_id', $this->ctx->membership()->user_id);
        }

        return $q;
    }

    protected function authorizeLead(Lead $lead): void
    {
        $this->ctx->ensureOwns($lead);
        $this->ctx->authorize('leads.manage');
        abort_unless($this->ctx->can('leads.view_all') || $lead->assigned_user_id === $this->ctx->membership()->user_id, 404);
    }
}
