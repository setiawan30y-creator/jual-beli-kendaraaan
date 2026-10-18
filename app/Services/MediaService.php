<?php

namespace App\Services;

use App\Models\UnitMedia;
use App\Models\VehicleUnit;
use App\Support\Audit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Penyimpanan bukti/foto per kategori (Blueprint §4.2.1).
 * Dokumen privat → disk "local" (storage/app/private, di luar webroot); foto unit → disk "public".
 */
class MediaService
{
    public function store(VehicleUnit $unit, string $category, UploadedFile $file, bool $publishConsent = false): UnitMedia
    {
        $meta = UnitMedia::CATEGORIES[$category];
        $private = $meta['private'];
        $disk = $private ? 'local' : 'public';
        $ext = strtolower($file->guessExtension() ?: 'jpg');
        $folder = ($unit->tenant_id ? 'tenants/'.$unit->tenant_id : 'sellers/'.$unit->seller_user_id).'/units/'.$unit->id.'/'.$category;
        $path = $file->storeAs($folder, Str::uuid().'.'.$ext, $disk);

        $media = UnitMedia::create([
            'unit_id' => $unit->id,
            'tenant_id' => $unit->tenant_id,
            'category' => $category,
            'disk' => $disk,
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'publish_consent' => ! $private && $publishConsent,
            'uploaded_by' => auth()->id(),
        ]);

        Audit::log('media.uploaded', $media, null, ['category' => $category, 'private' => $private], null, $unit->tenant_id);

        return $media;
    }

    public function delete(UnitMedia $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
        Audit::log('media.deleted', $media, ['category' => $media->category, 'status' => $media->verification_status], null, null, $media->tenant_id);
        $media->delete();
    }

    /** Ringkasan status per kategori untuk komponen VehicleMediaUploader. */
    public function checklist(VehicleUnit $unit): array
    {
        $grouped = $unit->media->groupBy('category');
        $out = [];
        foreach (UnitMedia::CATEGORIES as $key => $meta) {
            $items = $grouped->get($key, collect());
            $status = 'belum_diunggah';
            if ($items->isNotEmpty()) {
                $status = $items->contains('verification_status', 'perlu_perbaikan') ? 'perlu_perbaikan'
                    : ($items->every(fn ($m) => $m->verification_status === 'terverifikasi') ? 'terverifikasi' : 'menunggu_verifikasi');
            }
            $out[$key] = $meta + ['key' => $key, 'items' => $items, 'status' => $status];
        }

        return $out;
    }
}
