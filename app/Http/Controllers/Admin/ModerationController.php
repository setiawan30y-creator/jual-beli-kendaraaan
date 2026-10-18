<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\ListingService;
use App\Services\MediaService;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function index()
    {
        $listings = Listing::where('status', 'pending_review')
            ->with(['unit.vehicleModel.brand', 'unit.media', 'tenant', 'seller', 'city'])
            ->oldest('updated_at')->paginate(20);

        return view('admin.moderation.index', compact('listings'));
    }

    public function show(Listing $listing, MediaService $media)
    {
        $listing->load(['unit.vehicleModel.brand', 'unit.media', 'tenant', 'seller', 'city']);

        return view('admin.moderation.show', ['listing' => $listing, 'checklist' => $media->checklist($listing->unit)]);
    }

    public function approve(Request $request, Listing $listing, ListingService $service)
    {
        $service->approve($listing, $request->user());

        return redirect()->route('admin.moderation')->with('status', 'Iklan disetujui dan tayang.');
    }

    public function reject(Request $request, Listing $listing, ListingService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'alasan penolakan']);
        $service->reject($listing, $request->user(), $data['reason']);

        return redirect()->route('admin.moderation')->with('status', 'Iklan ditolak dengan alasan tercatat.');
    }
}
