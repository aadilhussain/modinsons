@extends('admin.layout')
@section('title', 'Settings')

@section('content')
<div class="adm-head">
  <div>
    <h1 class="h2">Settings</h1>
    <p class="small muted mt-1">These details feed the header, footer, contact page and enquiry pages across the site.</p>
  </div>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}">
  @csrf
  @method('PUT')

  <div class="cols-2" style="align-items:start">
    <div>
      <div class="panel">
        <div class="panel-head"><h3>Contact details</h3></div>
        <div class="panel-body">
          <div class="fgrid">
            <div class="field">
              <label for="phone">Phone <span class="req">*</span></label>
              <input id="phone" name="phone" required value="{{ old('phone', $biz['phone']) }}" placeholder="+918048202530">
              <div class="hint">Shown as a tap-to-call link.</div>
            </div>
            <div class="field">
              <label for="phone_alt">Alternate phone</label>
              <input id="phone_alt" name="phone_alt" value="{{ old('phone_alt', $biz['phone_alt'] ?? '') }}" placeholder="Optional second number">
            </div>
            <div class="field">
              <label for="whatsapp">WhatsApp number</label>
              <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $biz['whatsapp']) }}" placeholder="918048202530">
              <div class="hint">Country code, no “+” or spaces.</div>
            </div>
            <div class="field">
              <label for="email">Email <span class="req">*</span></label>
              <input id="email" name="email" type="email" required value="{{ old('email', $biz['email']) }}">
            </div>
            <div class="field full">
              <label for="hours">Opening hours</label>
              <input id="hours" name="hours" value="{{ old('hours', $biz['hours']) }}">
            </div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Address</h3></div>
        <div class="panel-body">
          <div class="fgrid">
            <div class="field full">
              <label for="line1">Street address <span class="req">*</span></label>
              <input id="line1" name="address[line1]" required value="{{ old('address.line1', $biz['address']['line1']) }}">
            </div>
            <div class="field">
              <label for="city">City <span class="req">*</span></label>
              <input id="city" name="address[city]" required value="{{ old('address.city', $biz['address']['city']) }}">
            </div>
            <div class="field">
              <label for="district">District</label>
              <input id="district" name="address[district]" value="{{ old('address.district', $biz['address']['district']) }}">
            </div>
            <div class="field">
              <label for="state">State</label>
              <input id="state" name="address[state]" value="{{ old('address.state', $biz['address']['state']) }}">
            </div>
            <div class="field">
              <label for="pincode">PIN code</label>
              <input id="pincode" name="address[pincode]" value="{{ old('address.pincode', $biz['address']['pincode']) }}">
            </div>
            <div class="field">
              <label for="country">Country</label>
              <input id="country" name="address[country]" value="{{ old('address.country', $biz['address']['country']) }}">
            </div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Google Map</h3></div>
        <div class="panel-body">
          <div class="field">
            <label for="map_embed">Map embed link</label>
            <textarea id="map_embed" name="map_embed" placeholder="https://www.google.com/maps/embed?pb=…">{{ old('map_embed', $biz['map_embed'] ?? '') }}</textarea>
            <div class="hint">
              On Google Maps: <strong class="strong">Share → Embed a map → Copy HTML</strong>, then paste it here.
              You can paste the whole <code>&lt;iframe&gt;</code> — the link is pulled out for you.
              Leave blank to auto-generate a map from the address above.
            </div>
          </div>
          @php
            $preview = ($biz['map_embed'] ?? '') ?: 'https://www.google.com/maps?q='.urlencode(
              $biz['address']['line1'].', '.$biz['address']['city'].', '.$biz['address']['state'].' '.$biz['address']['pincode']
            ).'&output=embed';
          @endphp
          <div style="border:1px solid var(--line);border-radius:var(--r);overflow:hidden;aspect-ratio:16/9;margin-top:12px">
            <iframe title="Current map preview" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    src="{{ $preview }}" style="width:100%;height:100%;border:0"></iframe>
          </div>
          <div class="hint mt-1">Preview of what the contact page currently shows.</div>
        </div>
      </div>
    </div>

    <div>
      <div class="panel">
        <div class="panel-head"><h3>Business identity</h3></div>
        <div class="panel-body">
          <div class="fgrid">
            <div class="field full">
              <label for="name">Display name <span class="req">*</span></label>
              <input id="name" name="name" required value="{{ old('name', $biz['name']) }}">
              <div class="hint">Used in the logo, page titles and footer.</div>
            </div>
            <div class="field full">
              <label for="legal_name">Legal / firm name <span class="req">*</span></label>
              <input id="legal_name" name="legal_name" required value="{{ old('legal_name', $biz['legal_name']) }}">
            </div>
            <div class="field full">
              <label for="tagline">Tagline</label>
              <input id="tagline" name="tagline" value="{{ old('tagline', $biz['tagline']) }}">
            </div>
            <div class="field">
              <label for="owner">Proprietor</label>
              <input id="owner" name="owner" value="{{ old('owner', $biz['owner']) }}">
            </div>
            <div class="field">
              <label for="established">Established</label>
              <input id="established" name="established" type="number" min="1900" max="2100"
                     value="{{ old('established', $biz['established']) }}">
            </div>
            <div class="field full">
              <label for="gst">GST number</label>
              <input id="gst" name="gst" value="{{ old('gst', $biz['gst']) }}">
            </div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Search engine settings</h3></div>
        <div class="panel-body">
          <div class="fgrid">
            <div class="field full">
              <label for="seo_description">Default meta description</label>
              <textarea id="seo_description" name="seo[description]" maxlength="320"
                        style="min-height:80px">{{ old('seo.description', $biz['seo']['description']) }}</textarea>
              <div class="hint">Shown under the title in Google for pages without their own description.
                Aim for 150–160 characters.</div>
            </div>
            <div class="field full">
              <label for="ga4">Google Analytics ID</label>
              <input id="ga4" name="ga4" value="{{ old('ga4', $biz['ga4']) }}" placeholder="G-XXXXXXXXXX">
              <div class="hint">Leave blank to disable analytics.</div>
            </div>
            <div class="field full">
              <label for="verification">Search Console verification</label>
              <input id="verification" name="seo[verification]"
                     value="{{ old('seo.verification', $biz['seo']['verification']) }}"
                     placeholder="Paste only the content value">
              <div class="hint">From Google Search Console → HTML tag method. Paste just the code,
                not the whole <code>&lt;meta&gt;</code> tag.</div>
            </div>
            <div class="field">
              <label for="lat">Map latitude</label>
              <input id="lat" name="geo[lat]" value="{{ old('geo.lat', $biz['geo']['lat']) }}" placeholder="24.9377">
            </div>
            <div class="field">
              <label for="lng">Map longitude</label>
              <input id="lng" name="geo[lng]" value="{{ old('geo.lng', $biz['geo']['lng']) }}" placeholder="73.8233">
              <div class="hint">Right-click your shop in Google Maps to copy both.</div>
            </div>
            <div class="field">
              <label for="price_range">Price range</label>
              <input id="price_range" name="seo[price_range]" value="{{ old('seo.price_range', $biz['seo']['price_range']) }}" placeholder="₹₹">
            </div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Listings &amp; social profiles</h3></div>
        <div class="panel-body">
          <p class="small muted mb-2">Linking these tells search engines the listings belong to this
            business, which strengthens the local result.</p>
          <div class="fgrid">
            @foreach ([
              'indiamart' => 'IndiaMART',
              'justdial'  => 'JustDial',
              'facebook'  => 'Facebook',
              'instagram' => 'Instagram',
            ] as $key => $label)
              <div class="field full">
                <label for="social_{{ $key }}">{{ $label }}</label>
                <input id="social_{{ $key }}" name="social[{{ $key }}]" type="url"
                       value="{{ old('social.'.$key, $biz['social'][$key] ?? '') }}"
                       placeholder="https://…">
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-body">
          <button class="btn btn-accent btn-lg btn-block" type="submit">
            <x-icon name="check" :size="17"/> Save business details</button>
        </div>
      </div>
    </div>
  </div>
</form>

<div class="cols-2" style="align-items:start">
  <div class="panel">
    <div class="panel-head"><h3>Your admin account</h3></div>
    <div class="panel-body">
      <form method="POST" action="{{ route('admin.settings.password') }}">
        @csrf
        @method('PUT')
        <div class="fgrid">
          <div class="field">
            <label for="acc_name">Name <span class="req">*</span></label>
            <input id="acc_name" name="name" required value="{{ old('name', $user->name) }}">
          </div>
          <div class="field">
            <label for="acc_email">Sign-in email <span class="req">*</span></label>
            <input id="acc_email" name="email" type="email" required value="{{ old('email', $user->email) }}">
          </div>
          <div class="field full">
            <label for="current_password">Current password <span class="req">*</span></label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
          </div>
          <div class="field">
            <label for="password">New password <span class="req">*</span></label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
            <div class="hint">At least 8 characters.</div>
          </div>
          <div class="field">
            <label for="password_confirmation">Confirm new password <span class="req">*</span></label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
          </div>
        </div>
        <button class="btn btn-accent mt-2" type="submit">
          <x-icon name="shield" :size="16"/> Update account</button>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Where these appear</h3></div>
    <div class="panel-body">
      <ul class="small muted" style="line-height:1.9;padding-left:18px">
        <li><strong class="strong">Phone &amp; WhatsApp</strong> — header, contact page, enquiry page, footer.</li>
        <li><strong class="strong">Alternate phone</strong> — contact page, next to the main number.</li>
        <li><strong class="strong">Email</strong> — contact page and enquiry notifications.</li>
        <li><strong class="strong">Address &amp; map</strong> — contact page and the search-engine listing.</li>
        <li><strong class="strong">Identity fields</strong> — page titles, About page and the footer.</li>
      </ul>
      <p class="small muted mt-2" style="border-top:1px solid var(--line);padding-top:12px">
        Changes go live immediately — no redeploy needed.
      </p>
    </div>
  </div>
</div>
@endsection
