<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    public function create(Request $request)
    {
        $product = null;

        if ($slug = $request->query('product')) {
            $product = Product::active()->where('slug', $slug)->first();
        }

        return view('enquiry', compact('product'));
    }

    public function store(Request $request)
    {
        // Honeypot — bots fill hidden fields, humans never see them.
        if (filled($request->input('website'))) {
            return back()->with('ok', 'Thank you — your enquiry has been received.');
        }

        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'name'       => ['required', 'string', 'max:120'],
            'company'    => ['nullable', 'string', 'max:160'],
            'phone'      => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'email'      => ['nullable', 'email', 'max:160'],
            'city'       => ['nullable', 'string', 'max:120'],
            'buyer_type' => ['required', Rule::in(['Wholesale', 'Distributor', 'Retailer', 'Contractor', 'Institutional', 'Individual'])],
            'quantity'   => ['nullable', 'string', 'max:60'],
            'unit'       => ['nullable', 'string', 'max:40'],
            'message'    => ['nullable', 'string', 'max:2000'],
        ], [
            'phone.regex' => 'Please enter a valid contact number.',
        ]);

        $data['reference']   = 'MS-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        $data['source_page'] = substr((string) $request->headers->get('referer'), 0, 255);
        $data['ip_hash']     = hash('sha256', $request->ip().config('app.key'));
        $data['user_agent']  = substr((string) $request->userAgent(), 0, 255);
        $data['status']      = 'new';

        $enquiry = Enquiry::create($data);

        return redirect()
            ->route('enquiry.thanks', ['ref' => $enquiry->reference])
            ->with('ok', 'Enquiry received.');
    }

    public function thanks(Request $request)
    {
        $enquiry = Enquiry::where('reference', $request->query('ref'))->firstOrFail();

        return view('enquiry-thanks', compact('enquiry'));
    }
}
