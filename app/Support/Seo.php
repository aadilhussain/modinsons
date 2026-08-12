<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Indexation rules for the catalogue's filterable, paginated listings.
 *
 * The same products can be reached through search terms, brand and category
 * filters, sort orders and page numbers. Left alone that is one page of content
 * addressable by dozens of URLs, which splits ranking signals and wastes crawl
 * budget. The rules here keep one canonical URL per distinct set of results.
 */
class Seo
{
    /** Query parameters that change which products are listed. */
    protected const FILTERS = ['q', 'brand', 'category'];

    /** Query parameters that only reorder or paginate an existing set. */
    protected const SORT = ['sort'];

    /**
     * The canonical URL for the current request.
     *
     * Page number is kept — page 2 is genuinely different content, and pointing
     * it at page 1 hides everything past the first screen from the index.
     * Filters and sort orders are dropped so they collapse onto the base page.
     */
    public static function canonical(Request $request): string
    {
        $page = (int) $request->query('page', 1);

        return $page > 1
            ? $request->url().'?page='.$page
            : $request->url();
    }

    /**
     * The robots directive for the current request.
     *
     * Filtered and sorted views stay crawlable so their links are followed, but
     * out of the index: they are thin permutations of pages that already rank.
     */
    public static function robots(Request $request): string
    {
        $hasFilter = false;

        foreach (array_merge(self::FILTERS, self::SORT) as $param) {
            if (filled($request->query($param))) {
                $hasFilter = true;
                break;
            }
        }

        return $hasFilter
            ? 'noindex,follow'
            : 'index,follow,max-image-preview:large';
    }
}
