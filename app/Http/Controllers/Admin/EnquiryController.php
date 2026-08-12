<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $enquiries = Enquiry::with('product')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('type'), fn ($q, $t) => $q->where('buyer_type', $t))
            ->when($request->query('q'), function ($q, $term) {
                $t = "%$term%";
                $q->where(fn ($w) => $w->where('name', 'like', $t)
                    ->orWhere('phone', 'like', $t)
                    ->orWhere('company', 'like', $t)
                    ->orWhere('reference', 'like', $t));
            })
            ->latest()
            ->paginate(25)->withQueryString();

        return view('admin.enquiries', [
            'enquiries' => $enquiries,
            'statuses'  => Enquiry::STATUSES,
            'counts'    => Enquiry::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $enquiry->update($request->validate([
            'status'     => ['required', 'in:'.implode(',', Enquiry::STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]));

        return back()->with('ok', 'Enquiry updated.');
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();

        return back()->with('ok', 'Enquiry deleted.');
    }

    public function export(): StreamedResponse
    {
        $name = 'enquiries-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Date', 'Name', 'Company', 'Phone', 'Email', 'City',
                'Buyer Type', 'Product', 'Quantity', 'Unit', 'Message', 'Status', 'Note']);

            Enquiry::with('product')->latest()->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    fputcsv($out, [
                        $e->reference, $e->created_at->format('Y-m-d H:i'), $e->name, $e->company,
                        $e->phone, $e->email, $e->city, $e->buyer_type,
                        $e->product?->name ?? 'General enquiry',
                        $e->quantity, $e->unit, $e->message, $e->status, $e->admin_note,
                    ]);
                }
            });

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }
}
