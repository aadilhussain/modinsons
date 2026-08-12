{{--
  BreadcrumbList for the page's trail. Expects $trail as [label => url], with
  the final entry's url null (the current page).

  Search engines use this to show the site's hierarchy in place of the raw URL
  in results, so the crumbs need to match what the page actually renders.
--}}
@php
  $items = [];
  $position = 1;

  foreach ($trail as $label => $url) {
      $entry = [
          '@type' => 'ListItem',
          'position' => $position++,
          'name' => $label,
      ];

      if ($url) {
          $entry['item'] = $url;
      }

      $items[] = $entry;
  }
@endphp
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => $items,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
