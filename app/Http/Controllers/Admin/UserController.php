<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $users = User::when($q, fn ($b) => $b->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->withCount('memberships')->latest()->paginate(30)->withQueryString();

        return view('admin.users', compact('users', 'q'));
    }

    public function status(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'Tidak dapat mengubah status akun sendiri.');
        abort_if($user->platform_role === 'super_admin', 403);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended'])], 'reason' => ['required', 'string', 'max:255']]);
        $before = $user->status;
        $user->forceFill(['status' => $data['status']])->save();
        if ($data['status'] === 'suspended') {
            $user->tokens()->delete();
        }
        Audit::log('platform.user_status_changed', $user, ['status' => $before], ['status' => $data['status']], $data['reason']);

        return back()->with('status', 'Status pengguna diperbarui.');
    }
}
