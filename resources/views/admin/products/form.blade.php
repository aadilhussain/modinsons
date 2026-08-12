@extends('admin.layout')
@section('title', $product->exists ? 'Edit Product' : 'Add Product')

@section('content')
<div class="adm-head">
  <div>
    <h1 class="h2">{{ $product->exists ? 'Edit product' : 'Add a product' }}</h1>
    <p class="small muted mt-1">Products show “Price on Enquiry” — no rates are published anywhere on the site.</p>
  </div>
  <a class="btn btn-outline btn-sm" href="{{ route('admin.products.index') }}">← Back to list</a>
</div>

<form method="POST" enctype="multipart/form-data"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
  @csrf
  @if ($product->exists) @method('PUT') @endif

  <div class="cols-2" style="align-items:start">
    <div class="panel">
      <div class="panel-head"><h3>Product details</h3></div>
      <div class="panel-body">
        <div class="fgrid">
          <div class="field full">
            <label for="name">Product name <span class="req">*</span></label>
            <input id="name" name="name" value="{{ old('name', $product->name) }}" required>
          </div>
          <div class="field">
            <label for="category_id">Category <span class="req">*</span></label>
            <select id="category_id" name="category_id" required>
              @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="brand">Brand</label>
            <input id="brand" name="brand" value="{{ old('brand', $product->brand) }}" placeholder="e.g. Polycab">
          </div>
          <div class="field">
            <label for="sku">SKU / code</label>
            <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
          </div>
          <div class="field">
            <label for="badge">Badge</label>
            <input id="badge" name="badge" value="{{ old('badge', $product->badge) }}" placeholder="Bestseller / New">
          </div>
          <div class="field">
            <label for="unit">Selling unit <span class="req">*</span></label>
            <select id="unit" name="unit" required>
              @foreach (['Piece','Metre','Coil','Kilogram','Set','Bundle','Square Feet','Bag','Box'] as $u)
                <option @selected(old('unit', $product->unit) === $u)>{{ $u }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="min_order_qty">Minimum order qty</label>
            <input id="min_order_qty" name="min_order_qty" value="{{ old('min_order_qty', $product->min_order_qty) }}" placeholder="e.g. 100 Metre">
          </div>
          <div class="field full">
            <label for="short_description">Short description</label>
            <input id="short_description" name="short_description" maxlength="255"
                   value="{{ old('short_description', $product->short_description) }}"
                   placeholder="One line shown on the product card and in search results.">
          </div>
          <div class="field full">
            <label for="description">Full description</label>
            <textarea id="description" name="description">{{ old('description', $product->description) }}</textarea>
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="panel">
        <div class="panel-head"><h3>Image</h3></div>
        <div class="panel-body">
          <img id="imagePreview" src="{{ $product->image_url }}" alt=""
               style="width:100%;max-width:190px;aspect-ratio:1;object-fit:contain;background:var(--bg-2);
                      border:1px solid var(--line);border-radius:var(--r);padding:12px;margin-bottom:12px">
          <div class="field">
            <label for="imageInput">Upload photo</label>
            <input id="imageInput" name="image" type="file" accept="image/jpeg,image/png,image/webp">
            <div class="hint">JPG, PNG or WebP up to 4 MB. Square photos on a white background look best.
              If you don't upload one, the category illustration is used.</div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Visibility</h3></div>
        <div class="panel-body">
          <label class="flex gap-1 items-center mb-2" style="cursor:pointer">
            <input type="checkbox" name="is_active" value="1" style="width:auto"
                   @checked(old('is_active', $product->is_active ?? true))>
            <span class="small strong">Live on the site</span>
          </label>
          <label class="flex gap-1 items-center mb-2" style="cursor:pointer">
            <input type="checkbox" name="is_featured" value="1" style="width:auto"
                   @checked(old('is_featured', $product->is_featured))>
            <span class="small strong">Show on homepage</span>
          </label>
          <div class="field">
            <label for="sort_order">Sort order</label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $product->sort_order ?? 0) }}">
            <div class="hint">Lower numbers appear first.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h3>Specifications</h3>
      <button class="btn btn-outline btn-sm" type="button" id="addSpec"><x-icon name="plus" :size="14"/> Add row</button>
    </div>
    <div class="panel-body">
      <div id="specRows">
        @php $specs = old('spec_key') ? array_combine(old('spec_key'), old('spec_val')) : ($product->specs ?: ['' => '']); @endphp
        @foreach ($specs as $k => $v)
          <div class="spec-row flex gap-1 mb-2 wrapf">
            <input name="spec_key[]" value="{{ $k }}" placeholder="Attribute (e.g. Size)"
                   style="flex:1;min-width:150px;padding:10px 12px;border:1.5px solid var(--line-2);border-radius:var(--r)">
            <input name="spec_val[]" value="{{ $v }}" placeholder="Value (e.g. 20 mm to 160 mm)"
                   style="flex:1.6;min-width:180px;padding:10px 12px;border:1.5px solid var(--line-2);border-radius:var(--r)">
            <button class="btn btn-danger btn-sm rm-spec" type="button" aria-label="Remove row"><x-icon name="trash" :size="14"/></button>
          </div>
        @endforeach
      </div>
      <p class="tiny muted">These appear as the specification table on the product page.</p>
    </div>
  </div>

  <div class="flex gap-1 wrapf">
    <button class="btn btn-primary btn-lg" type="submit">
      {{ $product->exists ? 'Save changes' : 'Add product' }}
    </button>
    <a class="btn btn-outline btn-lg" href="{{ route('admin.products.index') }}">Cancel</a>
  </div>
</form>
@endsection
