<?php

namespace App\Http\Controllers;

use App\Models\UnitMedia;
use App\Models\User;
use App\Models\VehicleUnit;
use App\Services\MediaService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Unggah bukti/foto per kategori dan akses dokumen privat.
 * Dokumen privat hanya dapat dibuka oleh pemilik unit, anggota tenant berizin,
 * atau moderator platform — setiap akses dicatat (Blueprint §4.2.1, §4.9).
 */
class MediaController extends Controller
{
    public function store(Request $request, VehicleUnit $unit, MediaService $media)
    {
        $this->authorizeManage($request->user(), $unit);
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(UnitMedia::CATEGORIES))],
            'file' => ['required', 'file', 'mimes:'.implode(',', config('marketplace.uploads.mimes')), 'max:'.config('marketplace.uploads.max_kb')],
            'publish_consent' => ['nullable', 'boolean'],
        ], [], ['file' => 'berkas']);

        $count = $unit->media()->where('category', $data['category'])->count();
        if ($count >= config('marketplace.uploads.per_category')) {
            return back()->withErrors(['file' => 'Batas jumlah berkas untuk kategori ini sudah tercapai. Hapus berkas lama terlebih dahulu.']);
        }

        $media->store($unit, $data['category'], $request->file('file'), (bool) ($data['publish_consent'] ?? false));

        return back()->with('status', UnitMedia::CATEGORIES[$data['category']]['label'].' berhasil diunggah.');
    }

    public function consent(Request $request, UnitMedia $media)
    {
        $this->authorizeManage($request->user(), $media->unit);
        abort_if($media->isPrivateCategory(), 422, 'Kategori ini bersifat privat dan tidak dapat dipublikasikan.');
        $media->publish_consent = $request->boolean('publish_consent');
        $media->save();
        Audit::log('media.consent_changed', $media, null, ['publish_consent' => $media->publish_consent], null, $media->tenant_id);

        return back()->with('status', 'Izin publikasi foto diperbarui.');
    }

    public function destroy(Request $request, UnitMedia $media, MediaService $service)
    {
        $this->authorizeManage($request->user(), $media->unit);
        if ($media->verification_status === 'terverifikasi' && ! $request->boolean('confirm_verified')) {
            return back()->withErrors(['file' => 'Berkas ini sudah terverifikasi. Centang konfirmasi untuk menghapusnya.']);
        }
        $service->delete($media);

        return back()->with('status', 'Berkas dihapus.');
    }

    public function show(Request $request, UnitMedia $media)
    {
        $user = $request->user();
        $unit = $media->unit;
        $allowed = $this->canManage($user, $unit)
            || ($unit->tenant_id && $user->activeMembershipFor($unit->tenant_id)?->can('media.private'))
            || $user->canModerate();
        abort_unless($allowed, 404);
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        if ($media->isPrivateCategory()) {
            Audit::log('media.private_viewed', $media, null, ['category' => $media->category], null, $media->tenant_id);
        }

        $response = response()->file(Storage::disk($media->disk)->path($media->path), [
            'Content-Type' => $media->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        // Jangan pernah disimpan cache publik/CDN/PWA.
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    protected function canManage(User $user, VehicleUnit $unit): bool
    {
        if ($unit->tenant_id === null) {
            return $unit->seller_user_id === $user->id;
        }

        return (bool) $user->activeMembershipFor($unit->tenant_id)?->can('units.manage');
    }

    protected function authorizeManage(User $user, VehicleUnit $unit): void
    {
        abort_unless($this->canManage($user, $unit), 404);
        if ($unit->tenant_id) {
            abort_if($unit->tenant?->status !== 'active', 403, 'Dealer tidak aktif.');
        }
    }
}
