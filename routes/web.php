<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnquiryController as AdminEnquiryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/* ------------------------- Public ------------------------- */
Route::get('/', [CatalogueController::class, 'home'])->name('home');
Route::get('/products', [CatalogueController::class, 'index'])->name('products');
Route::get('/category/{category}', [CatalogueController::class, 'category'])->name('category');
Route::get('/products/{product}', [CatalogueController::class, 'show'])->name('product');

Route::get('/enquiry', [EnquiryController::class, 'create'])->name('enquiry.create');
Route::post('/enquiry', [EnquiryController::class, 'store'])
    ->middleware('throttle:10,1')->name('enquiry.store');
Route::get('/enquiry/thank-you', [EnquiryController::class, 'thanks'])->name('enquiry.thanks');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

/* ------------------------- Auth --------------------------- */
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ------------------------- Admin -------------------------- */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{product}/toggle', [AdminProductController::class, 'toggle'])->name('products.toggle');

    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/enquiries', [AdminEnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('/enquiries/export', [AdminEnquiryController::class, 'export'])->name('enquiries.export');
    Route::put('/enquiries/{enquiry}', [AdminEnquiryController::class, 'update'])->name('enquiries.update');
    Route::delete('/enquiries/{enquiry}', [AdminEnquiryController::class, 'destroy'])->name('enquiries.destroy');
});

// TEMPORARY DIAGNOSTIC — remove once the login transaction issue is resolved.
Route::get('/__diag', function () {
    $out = [];
    $out['cache_store'] = config('cache.default');
    $out['session_driver'] = config('session.driver');
    $out['db_host'] = config('database.connections.pgsql.host');

    $steps = [
        'raw_select' => fn () => \DB::select('select 1 as ok')[0]->ok,
        'cache_add' => fn () => var_export(\Cache::add('diag_key', 0, 60), true),
        'cache_get' => fn () => var_export(\Cache::get('diag_key'), true),
        'cache_increment' => fn () => var_export(\Cache::increment('diag_key'), true),
        'session_table' => fn () => \DB::table('sessions')->count(),
    ];

    foreach ($steps as $name => $fn) {
        try {
            $out[$name] = 'OK: '.$fn();
        } catch (\Throwable $e) {
            $out[$name] = 'FAIL: '.get_class($e).' :: '.$e->getMessage();
        }
    }

    return response()->json($out, 200, [], JSON_PRETTY_PRINT);
});
