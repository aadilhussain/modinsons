@extends('admin.layout')
@section('title', 'Categories')

@section('content')
<div class="adm-head">
  <div><h1 class="h2">Categories</h1>
    <p class="small muted mt-1">Categories drive the top navigation and the homepage grid.</p></div>
</div>

<div class="cols-2" style="align-items:start">
  <div class="panel">
    <div class="panel-head"><h3>All categories</h3></div>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>Name</th><th>Products</th><th>Order</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($categories as $c)
          <tr>
            <td>
              <form method="POST" action="{{ route('admin.categories.update', $c) }}" class="flex gap-1 items-center wrapf">
                @csrf @method('PUT')
                <input name="name" value="{{ $c->name }}" required
                       style="padding:7px 10px;border:1px solid var(--line);border-radius:7px;font-weight:600;min-width:130px">
                <input name="tagline" value="{{ $c->tagline }}" placeholder="Tagline"
                       style="padding:7px 10px;border:1px solid var(--line);border-radius:7px;min-width:150px;font-size:13px">
                <input name="icon" value="{{ $c->icon }}" placeholder="icon"
                       style="padding:7px 10px;border:1px solid var(--line);border-radius:7px;width:86px;font-size:13px">
                <input name="sort_order" type="number" value="{{ $c->sort_order }}"
                       style="padding:7px 10px;border:1px solid var(--line);border-radius:7px;width:64px;font-size:13px">
                <input type="hidden" name="is_active" value="1">
                <button class="btn btn-outline btn-sm" type="submit">Save</button>
              </form>
            </td>
            <td class="small">{{ $c->products_count }}</td>
            <td class="small">{{ $c->sort_order }}</td>
            <td><span class="tag {{ $c->is_active ? 'tag-green' : 'tag-slate' }}">{{ $c->is_active ? 'Live' : 'Hidden' }}</span></td>
            <td>
              <form method="POST" action="{{ route('admin.categories.destroy', $c) }}"
                    data-confirm="Delete “{{ $c->name }}”?">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit"><x-icon name="trash" :size="14"/></button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty">No categories yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Add a category</h3></div>
    <div class="panel-body">
      <form method="POST" action="{{ route('admin.categories.store') }}">
        @csrf
        <div class="field mb-2">
          <label for="cname">Name <span class="req">*</span></label>
          <input id="cname" name="name" required placeholder="e.g. Switchgear">
        </div>
        <div class="field mb-2">
          <label for="ctag">Tagline</label>
          <input id="ctag" name="tagline" placeholder="Short line shown under the name">
        </div>
        <div class="field mb-2">
          <label for="cdesc">Description</label>
          <textarea id="cdesc" name="description" placeholder="Shown at the top of the category page."></textarea>
        </div>
        <div class="fgrid mb-2">
          <div class="field">
            <label for="cicon">Icon</label>
            <select id="cicon" name="icon">
              @foreach (['pipe','wire','fan','tablefan','led','motor','tarp','fence','switch','default'] as $i)
                <option>{{ $i }}</option>
              @endforeach
            </select>
            <div class="hint">Bundled illustration used when a product has no photo.</div>
          </div>
          <div class="field">
            <label for="csort">Sort order</label>
            <input id="csort" name="sort_order" type="number" min="0" value="0">
          </div>
        </div>
        <input type="hidden" name="is_active" value="1">
        <button class="btn btn-primary btn-block" type="submit"><x-icon name="plus" :size="15"/> Add Category</button>
      </form>
    </div>
  </div>
</div>
@endsection
