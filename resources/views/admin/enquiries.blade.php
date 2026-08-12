@extends('admin.layout')
@section('title', 'Enquiries')

@section('content')
<div class="adm-head">
  <div>
    <h1 class="h2">Enquiries</h1>
    <p class="small muted mt-1">{{ $enquiries->total() }} enquiries. Update the status as you work each lead.</p>
  </div>
  <a class="btn btn-outline btn-sm" href="{{ route('admin.enquiries.export') }}">
    <x-icon name="download" :size="15"/> Export CSV</a>
</div>

<div class="flex gap-1 wrapf mb-3">
  <a class="btn {{ request('status') ? 'btn-outline' : 'btn-primary' }} btn-sm" href="{{ route('admin.enquiries.index') }}">
    All <span style="opacity:.7">{{ $enquiries->total() }}</span></a>
  @foreach ($statuses as $s)
    <a class="btn {{ request('status') === $s ? 'btn-primary' : 'btn-outline' }} btn-sm"
       href="{{ route('admin.enquiries.index', ['status' => $s]) }}">
      {{ ucfirst($s) }} <span style="opacity:.7">{{ $counts[$s] ?? 0 }}</span></a>
  @endforeach
</div>

<div class="panel">
  <div class="panel-head">
    <form method="get" class="flex gap-1 items-center wrapf" style="flex:1">
      @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
      <input class="select" name="q" value="{{ request('q') }}" placeholder="Search name, phone, firm or reference…"
             style="min-width:220px;flex:1;max-width:340px">
      <button class="btn btn-outline btn-sm" type="submit">Search</button>
      @if (request('q'))<a class="btn btn-ghost btn-sm" href="{{ route('admin.enquiries.index') }}">Clear</a>@endif
    </form>
  </div>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr><th>Reference</th><th>Customer</th><th>Requirement</th><th>Status &amp; note</th><th></th></tr>
      </thead>
      <tbody>
      @forelse ($enquiries as $e)
        <tr>
          <td style="white-space:nowrap">
            <code class="tiny">{{ $e->reference }}</code>
            <div class="tiny muted mt-1">{{ $e->created_at->format('d M Y') }}</div>
            <div class="tiny faint">{{ $e->created_at->format('g:i A') }}</div>
          </td>
          <td>
            <strong class="strong">{{ $e->name }}</strong>
            @if ($e->company)<div class="tiny muted">{{ $e->company }}</div>@endif
            <div class="tiny"><a href="tel:{{ $e->phone }}" style="color:var(--navy-700);font-weight:600">{{ $e->phone }}</a></div>
            @if ($e->email)<div class="tiny muted">{{ $e->email }}</div>@endif
            <div class="mt-1 flex gap-1 wrapf">
              <span class="tag tag-slate">{{ $e->buyer_type }}</span>
              @if ($e->city)<span class="tiny muted">{{ $e->city }}</span>@endif
            </div>
          </td>
          <td style="max-width:320px">
            @if ($e->product)
              <a href="{{ route('product', $e->product) }}" target="_blank" class="strong small"
                 style="color:var(--navy-700)">{{ $e->product->name }}</a>
            @else
              <span class="small muted">General enquiry</span>
            @endif
            @if ($e->quantity)
              <div class="tiny muted mt-1">Qty: {{ $e->quantity }} {{ $e->unit }}</div>
            @endif
            @if ($e->message)
              <div class="tiny muted mt-1" style="white-space:pre-wrap">{{ Str::limit($e->message, 180) }}</div>
            @endif
          </td>
          <td style="min-width:230px">
            <form method="POST" action="{{ route('admin.enquiries.update', $e) }}">
              @csrf @method('PUT')
              <select name="status" class="select mb-1" style="width:100%">
                @foreach ($statuses as $s)
                  <option value="{{ $s }}" @selected($e->status === $s)>{{ ucfirst($s) }}</option>
                @endforeach
              </select>
              <textarea name="admin_note" placeholder="Internal note — rate quoted, follow-up date…"
                        style="width:100%;min-height:56px;padding:8px 10px;border:1px solid var(--line);
                               border-radius:7px;font-size:12.5px;resize:vertical">{{ $e->admin_note }}</textarea>
              <button class="btn btn-outline btn-sm mt-1" type="submit">Save</button>
            </form>
          </td>
          <td>
            <div class="flex gap-1" style="flex-direction:column">
              <a class="btn btn-wa btn-sm" target="_blank" rel="noopener"
                 href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $e->phone) }}?text={{ urlencode('Hello '.$e->name.', regarding your enquiry '.$e->reference.' with Modi And Sons —') }}">
                <x-icon name="wa" :size="13"/></a>
              <form method="POST" action="{{ route('admin.enquiries.destroy', $e) }}"
                    data-confirm="Delete enquiry {{ $e->reference }}?">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit"><x-icon name="trash" :size="13"/></button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty"><x-icon name="inbox" :size="42"/><p>No enquiries match this filter.</p></td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="pager">{{ $enquiries->links('partials.pager') }}</div>
@endsection
