<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /** Marks the enquiry bell as read for the current admin, without touching enquiry status. */
    public function seen(Request $request): Response
    {
        $request->user()->update(['enquiries_seen_at' => now()]);

        return response()->noContent();
    }
}
