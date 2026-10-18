<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Audit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(protected TenantContext $ctx) {}

    public function index(Request $request)
    {
        $this->ctx->authorize('bookings.manage');
        $bookings = $this->ctx->tenant()->bookings()->with(['listing', 'buyer'])
            ->orderByRaw("CASE status WHEN 'menunggu' THEN 0 WHEN 'dikonfirmasi' THEN 1 ELSE 2 END")
            ->orderBy('starts_at')->paginate(20);

        return view('dealer.bookings.index', compact('bookings'));
    }

    public function update(Request $request, Booking $booking)
    {
        $this->ctx->ensureOwns($booking);
        $this->ctx->authorize('bookings.manage');
        $data = $request->validate(['status' => ['required', Rule::in(['dikonfirmasi', 'selesai', 'dibatalkan'])]]);
        $allowed = ['menunggu' => ['dikonfirmasi', 'dibatalkan'], 'dikonfirmasi' => ['selesai', 'dibatalkan']];
        abort_unless(in_array($data['status'], $allowed[$booking->status] ?? [], true), 422, 'Perubahan status booking tidak diizinkan.');
        $from = $booking->status;
        $booking->status = $data['status'];
        $booking->save();
        Audit::log('booking.status_changed', $booking, ['status' => $from], ['status' => $booking->status]);

        return back()->with('status', 'Status booking diperbarui.');
    }
}
