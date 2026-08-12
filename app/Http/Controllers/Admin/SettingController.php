<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    /**
     * Config dot paths the settings form is allowed to write, mapped to their
     * validation rules. Anything outside this list is ignored, so a crafted
     * form post cannot reach unrelated config.
     */
    protected const FIELDS = [
        'name'             => ['required', 'string', 'max:120'],
        'legal_name'       => ['required', 'string', 'max:160'],
        'tagline'          => ['nullable', 'string', 'max:200'],
        'owner'            => ['nullable', 'string', 'max:120'],
        'established'      => ['nullable', 'integer', 'min:1900', 'max:2100'],
        'gst'              => ['nullable', 'string', 'max:40'],
        'phone'            => ['required', 'string', 'max:30'],
        'phone_alt'        => ['nullable', 'string', 'max:30'],
        'whatsapp'         => ['nullable', 'string', 'max:20'],
        'email'            => ['required', 'email', 'max:160'],
        'hours'            => ['nullable', 'string', 'max:160'],
        'address.line1'    => ['required', 'string', 'max:200'],
        'address.city'     => ['required', 'string', 'max:80'],
        'address.district' => ['nullable', 'string', 'max:80'],
        'address.state'    => ['nullable', 'string', 'max:80'],
        'address.pincode'  => ['nullable', 'string', 'max:12'],
        'address.country'  => ['nullable', 'string', 'max:80'],

        // SEO
        'seo.description'  => ['nullable', 'string', 'max:320'],
        'seo.price_range'  => ['nullable', 'string', 'max:12'],
        'seo.verification' => ['nullable', 'string', 'max:120'],
        'ga4'              => ['nullable', 'string', 'max:40'],
        'geo.lat'          => ['nullable', 'numeric', 'between:-90,90'],
        'geo.lng'          => ['nullable', 'numeric', 'between:-180,180'],
        'social.indiamart' => ['nullable', 'url', 'max:300'],
        'social.justdial'  => ['nullable', 'url', 'max:300'],
        'social.facebook'  => ['nullable', 'url', 'max:300'],
        'social.instagram' => ['nullable', 'url', 'max:300'],
    ];

    public function edit()
    {
        return view('admin.settings', [
            'biz'  => config('business'),
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        // The dot paths double as validation keys: the form posts the address
        // fields as address[line1], which is the shape these rules expect.
        $data = $request->validate(
            self::FIELDS + ['map_embed' => ['nullable', 'string', 'max:2000']]
        );

        $values = [];
        foreach (array_keys(self::FIELDS) as $path) {
            $values[$path] = (string) data_get($data, $path, '');
        }
        $values['map_embed'] = $this->normaliseMapEmbed($request->input('map_embed'));

        Setting::putMany($values);

        return back()->with('ok', 'Business details saved.');
    }

    public function password(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:120'],
            'email'            => ['required', 'email', 'max:160', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'That is not your current password.',
            'password.different' => 'Choose a password different from your current one.',
        ]);

        $user->update([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        return back()->with('ok', 'Account updated. Use your new password next time you sign in.');
    }

    /**
     * Accept either a bare Google Maps URL or a pasted <iframe> and keep only
     * the URL. Restricted to Google hosts so an embed cannot be pointed at an
     * arbitrary site.
     */
    protected function normaliseMapEmbed(?string $input): string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return '';
        }

        if (preg_match('/src=["\']([^"\']+)["\']/i', $input, $m)) {
            $input = $m[1];
        }

        $host = strtolower((string) parse_url($input, PHP_URL_HOST));
        $allowed = ['www.google.com', 'google.com', 'maps.google.com', 'www.google.co.in', 'maps.app.goo.gl', 'goo.gl'];

        if (! str_starts_with(strtolower($input), 'https://') || ! in_array($host, $allowed, true)) {
            throw ValidationException::withMessages([
                'map_embed' => 'Paste a Google Maps embed link — it must start with https:// and point at a google.com or goo.gl address.',
            ]);
        }

        return $input;
    }
}
