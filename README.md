# Modi And Sons — B2B catalogue & enquiry platform

Laravel 11 application for **Modi And Sons, Nathdwara** — electricals & hardware wholesaler,
distributor, supplier and retailer.

**Live site:** https://modinsons.vercel.app/

Quote-request catalogue: **no prices are shown anywhere**. Every product carries a
"Price on Enquiry" call-to-action that captures the buyer, the quantity and the buyer type.

---

## Setup (5 commands)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite      # SQLite default — skip if using MySQL
php artisan migrate --seed
php artisan storage:link
```

Then either `php artisan serve` (local) or point your web root at `public/`.

**Admin login:** `/login` — credentials are set in `database/seeders/DatabaseSeeder.php`
(not committed to this README). **Change the seeded password immediately after first sign-in.**

### Using MySQL instead
Edit `.env`:
```
DB_CONNECTION=mysql
DB_DATABASE=modisons
DB_USERNAME=your_user
DB_PASSWORD=your_pass
```
Then `php artisan migrate --seed`. No code changes needed.

### Shared hosting (cPanel / Hostinger)
1. Upload everything **above** `public/` outside the web root (e.g. `/home/user/modisons`).
2. Upload the contents of `public/` into `public_html`.
3. In `public_html/index.php`, update the two `require` paths to point at your app folder.
4. Run the setup commands over SSH, or use cPanel's Terminal / a PHP script for `migrate --seed`.

---

## What's built

**Public site** — home, catalogue with search + category + brand filters + sorting,
category pages, product detail with specification table, quote-request form, thank-you page
with reference number, about, contact with map, `sitemap.xml`, `robots.txt`.

**Admin panel** (`/admin`)
- **Dashboard** — page views and unique visitors (today and all-time), new/monthly/total
  enquiries, a 30-day SVG trend chart (views, visitors, enquiries), **per-page visitor counts**,
  most-viewed products, most-enquired products, enquiries by buyer type, pipeline status,
  device split and top referrers.
- **Products** — full CRUD, image upload with live preview, repeatable specification rows,
  featured toggle, live/hidden toggle, search and category filter.
- **Categories** — inline CRUD, sort order, icon picker.
- **Enquiries** — filter by status and buyer type, search, status pipeline
  (new → contacted → quoted → won → closed), internal notes, one-click WhatsApp reply,
  **CSV export**.

**Analytics** — `TrackPageView` middleware records every public GET. IP addresses are never
stored, only a salted SHA-256 hash, so unique visitors can be counted but not identified.
Bots are filtered out and views are de-duplicated per visitor per page per 30 minutes.
Set `GA4_ID` in `.env` to additionally enable Google Analytics 4.

---

## Verification status — read this

Composer/Packagist was unreachable in the environment where this was written, so
`vendor/` could not be installed and **the app was never booted**. What *was* verified:

- every PHP file passes `php -l` (syntax clean)
- every Blade `@if/@foreach/@forelse/@push/@section` is balanced
- every PHP expression inside Blade parses
- all 44 route names referenced in views exist in `routes/web.php`
- every `view()` target, `@extends`, and `@include` resolves to a real file
- every `config('business.*')` key exists
- every `<x-icon name="…">` exists in the icon component
- every category icon has a matching SVG

Not verified: runtime behaviour. Run the setup commands and click through
`/`, `/products`, a product page, `/enquiry`, and `/admin` before going live.
If anything errors, set `APP_DEBUG=true` temporarily — the trace will point straight at it.

---

## Before launch

1. **Change the admin password** (and the email in `database/seeders/DatabaseSeeder.php`).
2. **`config/business.php`** — verify the phone, email, full address and GST number.
   The IndiaMART listing showed the GST partly masked (`08**********1ZY`); put the real one in.
3. **Product photos.** The seeder ships 40 real products across 9 categories, but with
   *illustrations*, not photographs. Fix in bulk with `php artisan catalogue:images`
   (see below) — biggest single improvement available.
4. **Rates** — none are published, by design. Confirm you want it that way.
5. Set `APP_URL` and `APP_DEBUG=false`, then
   `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
6. Submit `sitemap.xml` in Google Search Console and claim the Google Business Profile —
   keep the name, address and phone identical to `config/business.php`.

---

## Product photos in bulk — `php artisan catalogue:images`

Photos are **committed** to `public/assets/products/*.webp`, not uploaded to
`storage/`. On Vercel the storage path is `/tmp` and is wiped between invocations,
so an admin upload survives locally but vanishes in production; `/assets` is served
statically (see `vercel.json`) and a committed file survives a deploy. Both routes
still work — `Product::image_url` resolves an `assets/…` path with `asset()` and
anything else through the public disk.

Every image is normalised to a square white-backed WebP (default 900×900, padded
rather than cropped) so the catalogue grid stays even.

**From a folder of files** — the surest route. Name each file after the product
code (`1001.jpg`, `MS-PVC-001.png`):

```bash
php artisan catalogue:images --dir=~/Desktop/toris-photos --dry-run   # check the pairing
php artisan catalogue:images --dir=~/Desktop/toris-photos
```

**From a spreadsheet of URLs:**

```bash
php artisan catalogue:images --worksheet=images.csv    # 1. products still missing a photo
#    fill the image_url column
php artisan catalogue:images --csv=images.csv          # 2. queue + download
```

An `image_url` column in a normal catalogue import is also picked up and queued —
imports never download, or a 300-row import would time out.

**From a supplier listing page** — proposes a photo per product by matching the
model code in the image filename or alt text. It writes a CSV and nothing else;
read it before importing, because a mispaired photo on a B2B catalogue is a
wrong-goods dispute:

```bash
php artisan catalogue:images --scan=https://example.com/products/x --worksheet=images.csv
```

Only point `--scan` at sources you have the right to use — your own photography, a
supplier's dealer portal, or a brand that has given you its asset pack. Brand
product photography is copyrighted, and distributor permission is usually a
one-line email away. The fetcher identifies itself in the User-Agent and pauses
between requests (`--delay`).

Other options: `--category=slug`, `--limit=n`, `--size=1200`, `--force` (replace
existing photos), `--dry-run`.

---

## Seeded catalogue

PVC Pipes & Fittings (6) · Electrical Wires & Cables (5) · Ceiling Fans (4) ·
Table & Wall Fans (4) · LED Lights & Panels (6) · Water Pumps & Motors (5) ·
Tarpaulin & Tirpal (3) · Fencing & Barbed Wire (4) · Electrical Accessories (4)

Sourced from the IndiaMART listing and expanded with the standard range for each line.
Edit or delete freely in the admin — nothing is hard-coded.
