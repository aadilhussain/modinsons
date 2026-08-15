@extends('admin.layout')
@section('title', 'Products')

@section('content')
<div class="adm-head">
  <div>
    <h1 class="h2">Products</h1>
    <p class="small muted mt-1">{{ $products->total() }} products in the catalogue.</p>
  </div>
  <div class="flex gap-1 items-center" style="flex-wrap:wrap">
    <a class="btn btn-outline btn-sm" href="{{ route('admin.products.import') }}">
      <x-icon name="layers" :size="15"/> Import</a>
    <a class="btn btn-outline btn-sm" href="{{ route('admin.products.export') }}">
      <x-icon name="download" :size="15"/> Export CSV</a>
    <a class="btn btn-primary btn-sm" href="{{ route('admin.products.create') }}"><x-icon name="plus" :size="15"/> Add Product</a>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <form method="get" class="flex gap-1 items-center wrapf" style="flex:1">
      <input class="select" name="q" value="{{ request('q') }}" placeholder="Search name, brand or SKU…" style="min-width:200px;flex:1;max-width:320px">
      <select class="select" name="category" data-autosubmit>
        <option value="">All categories</option>
        @foreach ($categories as $c)
          <option value="{{ $c->id }}" @selected(request('category')==$c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      <select class="select" name="sort" data-autosubmit aria-label="Sort products">
        @foreach ($sorts as $key => $label)
          <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
        @endforeach
      </select>
      <button class="btn btn-outline btn-sm" type="submit">Filter</button>
      @if (request('q') || request('category') || request('sort'))
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.products.index') }}">Clear</a>
      @endif
    </form>
  </div>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th></th><th>Product</th><th>Category</th><th>Brand</th>
        {{-- Internal rate: shown here only, never on the public catalogue. --}}
        <th>Rate</th><th>MOQ</th><th>Stock</th><th>Added</th><th>Views</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse ($products as $p)
        <tr>
          <td><img src="{{ $p->image_url }}" alt=""></td>
          <td>
            <strong class="strong">{{ $p->name }}</strong>
            <div class="tiny muted">{{ $p->sku }}@if($p->is_featured) · <span style="color:var(--saffron-600)">Featured</span>@endif</div>
          </td>
          <td class="small">{{ $p->category?->name }}</td>
          <td class="small">{{ $p->brand ?: '—' }}</td>
          <td class="small">
            @if (! is_null($p->price))
              <span class="strong">₹{{ number_format((float) $p->price, 2) }}</span>
            @else
              —
            @endif
          </td>
          <td class="small">{{ $p->min_order_qty ?: '—' }}</td>
          <td class="small">
            @if (is_null($p->stock_qty))
              <span class="muted">—</span>
            @elseif ($p->is_out_of_stock)
              <span class="tag tag-red">Out of stock</span>
            @elseif ($p->is_low_stock)
              <span class="tag tag-amber">{{ $p->stock_qty }} left</span>
            @else
              {{ number_format($p->stock_qty) }}
            @endif
          </td>
          <td class="small" title="{{ $p->created_at?->format('d M Y, g:i a') }}">
            {{ $p->created_at?->format('d M Y') ?: '—' }}</td>
          <td class="small">{{ number_format($p->views) }}</td>
          <td>
            <form method="POST" action="{{ route('admin.products.toggle', $p) }}">
              @csrf
              <button type="submit" style="all:unset;cursor:pointer">
                <span class="tag {{ $p->is_active ? 'tag-green' : 'tag-slate' }}">{{ $p->is_active ? 'Live' : 'Hidden' }}</span>
              </button>
            </form>
          </td>
          <td>
            <div class="flex gap-1">
              <a class="btn btn-outline btn-sm" href="{{ route('admin.products.edit', $p) }}"><x-icon name="edit" :size="14"/></a>
              <form method="POST" action="{{ route('admin.products.destroy', $p) }}"
                    data-confirm="Delete “{{ $p->name }}”? This cannot be undone.">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit"><x-icon name="trash" :size="14"/></button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="11" class="empty"><x-icon name="box" :size="42"/><p>No products yet.</p>
          <a class="btn btn-primary mt-2" href="{{ route('admin.products.create') }}">Add your first product</a></td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="pager">{{ $products->links('partials.pager') }}</div>
@endsection
