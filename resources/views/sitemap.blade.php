{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- The declaration must be the first bytes of the document. Its opening "<" and "?" are split because, with short_open_tag=On, Blade's tokenizer reads them together as a PHP tag and leaves the line uncompiled --}}
@php
    $lastModified = fn ($posts) => $posts
        ->map(fn ($post) => \Jessecruz\SimpleBlog\Support\Seo::lastModified($post))
        ->max();
    $indexLastModified = $lastModified($posts);
@endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('blog.index') }}</loc>
        @if($indexLastModified)
            <lastmod>{{ $indexLastModified->toAtomString() }}</lastmod>
        @endif
    </url>
    @foreach($categories as $category)
        <url>
            <loc>{{ route('blog.category', $category) }}</loc>
            <lastmod>{{ $lastModified($posts->where('blog_category_id', $category->id))->toAtomString() }}</lastmod>
        </url>
    @endforeach
    @foreach($posts as $post)
        <url>
            <loc>{{ $post->url() }}</loc>
            <lastmod>{{ \Jessecruz\SimpleBlog\Support\Seo::lastModified($post)->toAtomString() }}</lastmod>
        </url>
    @endforeach
</urlset>
