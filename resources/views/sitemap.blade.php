<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{{ config('app.url') }}/</loc>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  @foreach ($courses as $course)
  <url>
    <loc>{{ config('app.url') }}/course/{{ $course->id }}</loc>
    <lastmod>{{ $course->updated_at->toAtomString() }}</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  @endforeach
</urlset>
