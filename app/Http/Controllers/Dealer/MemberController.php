<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\TenantMembership;
use App\Models\User;
use App\Services\PlanLimits;
use App\Support\Audit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function __construct(protected TenantContext $ctx) {}

    public function index(PlanLimits $limits)
    {
        $this->ctx->authorize('members.manage');

        return view('dealer.members', [
            'members' => $this->ctx->tenant()->memberships()->with(['user', 'branch'])->orderBy('status')->get(),
            'branches' => $this->ctx->tenant()->branches()->get(),
            'roles' => collect(TenantMembership::ROLES)->except('tenant_owner'),
            'usage' => $limits->summary($this->ctx->tenant())['staff_limit'],
        ]);
    }

    /** MVP: menambahkan pengguna yang sudah terdaftar berdasarkan email. */
    public function store(Request $request, PlanLimits $limits)
    {
        $this->ctx->authorize('members.manage');
        $tenant = $this->ctx->tenant();
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(array_keys(TenantMembership::ROLES)), Rule::notIn(['tenant_owner'])],
            'branch_id' => ['nullable', Rule::exists('tenant_branches', 'id')->where('tenant_id', $tenant->id)],
        ], ['email.exists' => 'Pengguna dengan email ini belum terdaftar. Minta staf mendaftar terlebih dahulu.']);

        if (! $limits->canConsume($tenant, 'staff_limit')) {
            return back()->withErrors(['email' => 'Kuota anggota staf pada paket Anda sudah penuh.']);
        }
        $user = User::where('email', $data['email'])->first();
        if ($tenant->memberships()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['email' => 'Pengguna ini sudah menjadi anggota.']);
        }
        $m = TenantMembership::create([
            'tenant_id' => $tenant->id, 'user_id' => $user->id, 'branch_id' => $data['branch_id'] ?? null,
            'role' => $data['role'], 'status' => 'active', 'invited_by' => $request->user()->id, 'joined_at' => now(),
        ]);
        Audit::log('member.added', $m, null, ['role' => $m->role, 'user_id' => $user->id]);

        return back()->with('status', $user->name.' ditambahkan sebagai '.$m->roleLabel().'.');
    }

    public function update(Request $request, TenantMembership $membership)
    {
        $this->ctx->ensureOwns($membership);
        $this->ctx->authorize('members.manage');
        abort_if($membership->role === 'tenant_owner', 422, 'Peran pemilik tidak dapat diubah di sini.');
        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(TenantMembership::ROLES)), Rule::notIn(['tenant_owner'])],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'branch_id' => ['nullable', Rule::exists('tenant_branches', 'id')->where('tenant_id', $this->ctx->id())],
        ]);
        $before = $membership->only(['role', 'status', 'branch_id']);
        $membership->update($data);
        Audit::log('member.updated', $membership, $before, $membership->only(['role', 'status', 'branch_id']));

        return back()->with('status', 'Anggota diperbarui.');
    }
}
