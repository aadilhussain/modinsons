@extends('layouts.app')
@section('title', 'Contact Us — Nathdwara, Rajasthan | '.config('business.name'))
@section('meta', 'Contact Modi And Sons, Nathdwara Road, Nathdwara, Rajsamand, Rajasthan. Call, WhatsApp or send a wholesale enquiry for electricals and hardware.')

@push('schema')
<script type="application/ld+json">
{!! json_encode([
  '@context'=>'https://schema.org','@type'=>'ContactPage',
  'mainEntity'=>[
    '@type'=>'Organization','name'=>config('business.legal_name'),
    'telephone'=>config('business.phone'),'email'=>config('business.email'),
  ],
], JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
@php $biz = config('business'); @endphp
<div class="wrap"><nav class="crumbs"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Contact</span></nav></div>

<section class="sec-sm" style="padding-top:0">
  <div class="wrap">
    <span class="eyebrow">Get In Touch</span>
    <h1 class="h1 mb-3">Call, message or visit the counter</h1>

    <div class="grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr))">
      @foreach ([
        ['phone','Call us', $biz['phone'], 'tel:'.$biz['phone']],
        ['wa','WhatsApp', 'Send your item list', 'https://wa.me/'.$biz['whatsapp']],
        ['mail','Email', $biz['email'], 'mailto:'.$biz['email']],
        ['clock','Open', $biz['hours'], null],
      ] as [$ic,$t,$v,$href])
        <div class="form-card rv">
          <span style="width:42px;height:42px;border-radius:10px;background:var(--navy-100);color:var(--navy-700);
                       display:grid;place-items:center;margin-bottom:12px"><x-icon :name="$ic" :size="20"/></span>
          <div class="tiny muted" style="text-transform:uppercase;letter-spacing:.09em;font-weight:700">{{ $t }}</div>
          @if ($href)
            <a href="{{ $href }}" @if(Str::startsWith($href,'http')) target="_blank" rel="noopener" @endif
               class="strong" style="font-size:14.5px;display:block;margin-top:4px">{{ $v }}</a>
          @else
            <div class="strong mt-1" style="font-size:14.5px">{{ $v }}</div>
          @endif
        </div>
      @endforeach
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:clamp(20px,3vw,36px)" class="ct-grid">
      <div class="form-card">
        <h3 class="h3 mb-2">Visit our counter</h3>
        <p class="muted mb-2">{{ $biz['address']['line1'] }}<br>
          {{ $biz['address']['city'] }}, {{ $biz['address']['district'] }}<br>
          {{ $biz['address']['state'] }} {{ $biz['address']['pincode'] }}, {{ $biz['address']['country'] }}</p>
        <p class="small muted mb-2"><strong class="strong">Timings:</strong> {{ $biz['hours'] }}</p>
        <div style="border:1px solid var(--line);border-radius:var(--r);overflow:hidden;aspect-ratio:16/10">
          <iframe title="Modi And Sons location, Nathdwara" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps?q={{ urlencode($biz['address']['line1'].', '.$biz['address']['city'].', '.$biz['address']['state'].' '.$biz['address']['pincode']) }}&output=embed"
            style="width:100%;height:100%;border:0"></iframe>
        </div>
      </div>

      <div class="form-card">
        <h3 class="h3 mb-2">Send an enquiry</h3>
        <p class="small muted mb-3">For rates and stock, the enquiry form gets you a faster, more accurate answer
          than a general message — it captures quantity and buyer type up front.</p>
        <a class="btn btn-accent btn-lg btn-block" href="{{ route('enquiry.create') }}">
          <x-icon name="quote" :size="17"/> Request a Wholesale Quote</a>
        <a class="btn btn-wa btn-block mt-2" target="_blank" rel="noopener"
           href="https://wa.me/{{ $biz['whatsapp'] }}"><x-icon name="wa" :size="17"/> Chat on WhatsApp</a>
        <a class="btn btn-outline btn-block mt-2" href="tel:{{ $biz['phone'] }}">
          <x-icon name="phone" :size="17"/> Call {{ $biz['phone'] }}</a>
        <div class="mt-3 pt-2" style="border-top:1px solid var(--line)">
          <div class="tiny muted" style="text-transform:uppercase;letter-spacing:.09em;font-weight:700;margin-bottom:8px">Also find us on</div>
          <a class="small" style="color:var(--navy-700);font-weight:600" href="https://www.indiamart.com/modi-and-sons-nathdwara/" target="_blank" rel="noopener nofollow">IndiaMART →</a>
          <a class="small mt-1" style="color:var(--navy-700);font-weight:600;display:block" href="https://www.justdial.com/Nathdwara/Modi-and-Sons-Nathdwara-Road/" target="_blank" rel="noopener nofollow">JustDial →</a>
        </div>
      </div>
    </div>
  </div>
</section>
<style>@media(max-width:860px){.ct-grid{grid-template-columns:1fr!important}}</style>
@endsection
