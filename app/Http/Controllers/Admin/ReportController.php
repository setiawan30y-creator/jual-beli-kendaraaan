<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingReport;
use App\Services\ListingService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['open', 'reviewed', 'dismissed'], true) ? $request->query('status') : 'open';
        $reports = ListingReport::where('status', $status)->with(['listing', 'reporter'])->latest()->paginate(25)->withQueryString();

        return view('admin.reports', compact('reports', 'status'));
    }

    public function update(Request $request, ListingReport $report, ListingService $listings)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['reviewed', 'dismissed'])],
            'archive_listing' => ['nullable', 'boolean'],
            'note' => ['required', 'string', 'max:255'],
        ], [], ['note' => 'catatan tindakan']);
        $report->status = $data['status'];
        $report->reviewed_by = $request->user()->id;
        $report->save();
        if ($request->boolean('archive_listing') && in_array($report->listing->status, ['published', 'reserved', 'pending_review', 'draft', 'rejected', 'expired'], true)) {
            $l = $report->listing;
            if ($l->status === 'pending_review') {
                $listings->reject($l, $request->user(), 'Ditindak dari laporan: '.$data['note']);
            } else {
                $listings->transition($l, 'archived', 'Ditindak dari laporan: '.$data['note']);
            }
        }
        Audit::log('report.resolved', $report, null, ['status' => $data['status']], $data['note'], $report->listing->tenant_id);

        return back()->with('status', 'Laporan diperbarui.');
    }
}
