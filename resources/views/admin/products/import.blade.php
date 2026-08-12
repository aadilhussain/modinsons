@extends('admin.layout')
@section('title', 'Import Products')

@section('content')
<div class="adm-head">
  <div>
    <h1 class="h2">Import products</h1>
    <p class="small muted mt-1">Bring in a supplier catalogue from a spreadsheet. Nothing is saved until you
      review the rows and confirm.</p>
  </div>
  <a class="btn btn-outline btn-sm" href="{{ route('admin.products.index') }}">← Back to products</a>
</div>

@if (! $preview)
  <div class="cols-2" style="align-items:start">
    <div class="panel">
      <div class="panel-head"><h3>Upload a file</h3></div>
      <div class="panel-body">
        <form method="POST" action="{{ route('admin.products.import.preview') }}" enctype="multipart/form-data">
          @csrf
          <div class="field mb-2">
            <label for="file">Catalogue file <span class="req">*</span></label>
            <input id="file" name="file" type="file" accept=".csv,.txt,.pdf" required
                   data-max-bytes="{{ $maxMb * 1000 * 1000 }}">
            <div class="hint">CSV or PDF, up to {{ $maxMb }} MB. In Excel: <strong class="strong">File → Save As → CSV</strong>.</div>
            {{-- Oversized uploads are dropped by the host before any PHP runs,
                 which would show a bare error page, so they are caught here. --}}
            <div id="size-warning" class="hint" style="display:none;color:var(--red-600);font-weight:600"></div>
          </div>
          <div class="field mb-2">
            <label for="default_category">Fallback category</label>
            <input id="default_category" name="default_category" list="catlist"
                   placeholder="Used when a row has no category of its own">
            <datalist id="catlist">
              @foreach ($categories as $c)<option value="{{ $c->name }}">@endforeach
            </datalist>
          </div>
          <button class="btn btn-accent btn-block" type="submit" id="go">
            <x-icon name="download" :size="17"/> Read the file</button>
        </form>
        <script>
          (function () {
            var input = document.getElementById('file'),
                warn  = document.getElementById('size-warning'),
                go    = document.getElementById('go'),
                max   = parseInt(input.dataset.maxBytes, 10);

            input.addEventListener('change', function () {
              var f = input.files[0];
              if (!f) { warn.style.display = 'none'; go.disabled = false; return; }

              if (f.size > max) {
                warn.textContent = 'That file is ' + (f.size / 1000000).toFixed(1) + ' MB — too big to upload. '
                  + 'Picture-heavy catalogues are almost always scans with no readable text in them anyway. '
                  + 'Ask your supplier for the product list as a spreadsheet.';
                warn.style.display = 'block';
                go.disabled = true;
              } else {
                warn.style.display = 'none';
                go.disabled = false;
              }
            });
          })();
        </script>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>How it works</h3></div>
      <div class="panel-body">
        <ul class="small muted" style="line-height:1.9;padding-left:18px">
          <li>Your column headings are matched automatically — <em>Name</em>, <em>Product</em> and
            <em>Item Name</em> all mean the same thing, as do <em>SKU</em>, <em>Code</em> and <em>Model</em>.</li>
          <li>Any column we don't recognise (size, trap, material, finish…) is kept as a product
            specification rather than thrown away.</li>
          <li>Rows matching an existing product — by code first, then by name — are marked as
            <strong class="strong">updates</strong> instead of creating duplicates.</li>
          <li>You choose row by row what gets imported.</li>
        </ul>
        <div class="mt-3 pt-2" style="border-top:1px solid var(--line)">
          <div class="tiny muted" style="text-transform:uppercase;letter-spacing:.09em;font-weight:700;margin-bottom:8px">
            About PDFs</div>
          <p class="small muted">A PDF only works if its text is real text. Catalogues that were scanned, or
            exported as pictures from Photoshop, hold no readable text at all — you'll be told if that's the
            case. Ask the supplier for a spreadsheet instead.</p>
        </div>
        <div class="mt-3 pt-2" style="border-top:1px solid var(--line)">
          <p class="small muted">Not sure of the format? <a class="strong" style="color:var(--navy-700)"
            href="{{ route('admin.products.export') }}">Export your current products</a> — that file
            imports straight back, so it doubles as the template.</p>
        </div>
      </div>
    </div>
  </div>
@else
  @php
    $rows = $preview['rows'];
    $creates = collect($rows)->where('action', 'create')->count();
    $updates = collect($rows)->where('action', 'update')->count();
    // Not $errors — that name is Laravel's shared ViewErrorBag, and shadowing it
    // breaks the surrounding layout.
    $rejected = collect($rows)->where('action', 'error')->count();
    $newCats = collect($rows)->where('new_category', true)->pluck('category')->unique()->filter()->values();
  @endphp

  <div class="panel">
    <div class="panel-head">
      <h3>Review “{{ $preview['file'] }}”</h3>
      <form method="POST" action="{{ route('admin.products.import.discard') }}">
        @csrf <button class="btn btn-outline btn-sm" type="submit">Discard</button>
      </form>
    </div>
    <div class="panel-body">
      <div class="flex gap-2 items-center mb-2" style="flex-wrap:wrap">
        <span class="tag tag-green">{{ $creates }} new</span>
        <span class="tag tag-slate">{{ $updates }} will update</span>
        @if ($rejected)<span class="tag tag-red">{{ $rejected }} cannot import</span>@endif
      </div>

      @foreach ($preview['notes'] as $note)
        <p class="small muted mb-2">{{ $note }}</p>
      @endforeach

      @if ($newCats->isNotEmpty())
        <div class="alert alert-ok mb-2" style="align-items:flex-start">
          <x-icon name="grid"/>
          <span>These categories don't exist yet: <strong class="strong">{{ $newCats->join(', ') }}</strong>.
            Tick the box below to create them, otherwise those rows are skipped.</span>
        </div>
      @endif
    </div>
  </div>

  <form method="POST" action="{{ route('admin.products.import.store') }}">
    @csrf
    <div class="panel">
      <div class="tbl-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:38px"><input type="checkbox" id="all" checked aria-label="Select all"></th>
              <th>Line</th><th>Product</th><th>Code</th><th>Category</th><th>Specs</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
          @foreach ($rows as $row)
            <tr class="{{ $row['action'] === 'error' ? 'row-err' : '' }}">
              <td>
                <input type="checkbox" name="accept[]" value="{{ $row['line'] }}"
                       class="pick" @checked($row['action'] !== 'error') @disabled($row['action'] === 'error')>
              </td>
              <td class="small muted">{{ $row['line'] }}</td>
              <td>
                <strong class="strong">{{ $row['data']['name'] ?: '—' }}</strong>
                @if ($row['data']['brand'])<div class="tiny muted">{{ $row['data']['brand'] }}</div>@endif
                @foreach ($row['errors'] as $err)
                  <div class="tiny" style="color:var(--red-600)">{{ $err }}</div>
                @endforeach
              </td>
              <td class="small">{{ $row['data']['sku'] ?: '—' }}</td>
              <td class="small">
                {{ $row['category'] ?: '—' }}
                @if ($row['new_category'])<span class="tag tag-amber" style="margin-left:4px">new</span>@endif
              </td>
              <td class="tiny muted">
                @foreach (($row['data']['specs'] ?? []) as $k => $v)
                  <div>{{ $k }}: {{ $v }}</div>
                @endforeach
              </td>
              <td>
                <span class="tag {{ ['create'=>'tag-green','update'=>'tag-slate','error'=>'tag-red'][$row['action']] ?? 'tag-slate' }}">
                  {{ ['create'=>'Add','update'=>'Update','error'=>'Skip'][$row['action']] ?? $row['action'] }}
                </span>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <div class="panel-body">
        @if ($newCats->isNotEmpty())
          <label class="flex gap-1 items-center mb-2" style="cursor:pointer">
            <input type="checkbox" name="create_categories" value="1" checked>
            <span class="small">Create the {{ $newCats->count() }} missing
              {{ Str::plural('category', $newCats->count()) }}</span>
          </label>
        @endif
        <button class="btn btn-accent btn-lg" type="submit">
          <x-icon name="check" :size="17"/> Import the ticked rows</button>
      </div>
    </div>
  </form>

  <style>.row-err{background:#fff5f5}</style>
  <script>
    document.getElementById('all')?.addEventListener('change', function () {
      document.querySelectorAll('.pick:not(:disabled)').forEach(c => { c.checked = this.checked });
    });
  </script>
@endif
@endsection
