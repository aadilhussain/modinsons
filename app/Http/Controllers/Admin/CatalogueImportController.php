<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\Import\CatalogueImporter;
use App\Support\Import\CatalogueMapper;
use App\Support\Import\CatalogueReader;
use App\Support\Import\ImportException;
use Illuminate\Http\Request;

class CatalogueImportController extends Controller
{
    /** Where the parsed rows wait between the preview and the confirmation. */
    protected const SESSION_KEY = 'catalogue_import';

    /**
     * Uploads are capped well under the 4.5 MB body limit imposed by the
     * serverless platform — a larger file is rejected by the edge before any
     * code runs, which would otherwise look like a broken page.
     */
    protected const MAX_KB = 4000;

    public function create()
    {
        return view('admin.products.import', [
            'categories' => Category::orderBy('name')->get(),
            'preview' => session(self::SESSION_KEY),
            'maxMb' => round(self::MAX_KB / 1000, 1),
        ]);
    }

    public function preview(Request $request, CatalogueReader $reader, CatalogueMapper $mapper)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,pdf', 'max:'.self::MAX_KB],
            'default_category' => ['nullable', 'string', 'max:120'],
        ], [
            'file.max' => 'That file is larger than '.round(self::MAX_KB / 1000, 1).' MB. Split it, or save just the product list as CSV.',
            'file.mimes' => 'Upload a CSV or a PDF. In Excel use File → Save As → CSV.',
        ]);

        $file = $request->file('file');

        try {
            $read = $reader->read($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (ImportException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $rows = $mapper->map($read['rows'], (string) $request->input('default_category', ''));

        $request->session()->put(self::SESSION_KEY, [
            'file' => $file->getClientOriginalName(),
            'rows' => $rows,
            'notes' => $read['notes'],
        ]);

        return redirect()->route('admin.products.import');
    }

    public function store(Request $request, CatalogueImporter $importer)
    {
        $preview = session(self::SESSION_KEY);

        if (! $preview) {
            return redirect()->route('admin.products.import')
                ->withErrors(['file' => 'That preview has expired. Upload the file again.']);
        }

        $data = $request->validate([
            'accept' => ['required', 'array', 'min:1'],
            'accept.*' => ['integer'],
        ], [
            'accept.required' => 'Tick at least one row to import.',
        ]);

        $result = $importer->import(
            $preview['rows'],
            $data['accept'],
            $request->boolean('create_categories')
        );

        $request->session()->forget(self::SESSION_KEY);

        $parts = [];
        if ($result['created']) {
            $parts[] = $result['created'].' added';
        }
        if ($result['updated']) {
            $parts[] = $result['updated'].' updated';
        }
        if ($result['skipped']) {
            $parts[] = $result['skipped'].' skipped';
        }
        if ($result['categories']) {
            $parts[] = 'new categories: '.implode(', ', $result['categories']);
        }

        return redirect()->route('admin.products.index')
            ->with('ok', 'Import finished — '.(implode(', ', $parts) ?: 'nothing to do').'.');
    }

    public function discard(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('admin.products.import')->with('ok', 'Preview discarded.');
    }
}
