{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- The declaration must be the first bytes of the document; it is printed as a string so PHP's short_open_tag can't mistake it for code --}}
@php
    $seo = \Jessecruz\SimpleBlog\Support\Seo::class;
    $lastBuild = $posts->map(fn ($post) => $seo::lastModified($post))->max();
@endphp
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <title>{{ $seo::blogName() }}</title>
        <link>{{ route('blog.index') }}</link>
        <description>{{ $seo::indexDescription() ?? $seo::blogName() }}</description>
        <language>{{ $seo::language() }}</language>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml" />
        @if($lastBuild)
            <lastBuildDate>{{ $lastBuild->toRfc2822String() }}</lastBuildDate>
        @endif
        @foreach($posts as $post)
            @php($image = $seo::postImage($post))
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ $post->url() }}</link>
                <guid isPermaLink="true">{{ $post->url() }}</guid>
                <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
                <dc:creator>{{ $post->authorName() }}</dc:creator>
                <category>{{ $post->category->name }}</category>
                <description>{{ $post->excerpt }}</description>
                @if($fullContent)
                    {{-- A literal "]]>" in the body would close the CDATA section early --}}
                    <content:encoded><![CDATA[{!! str_replace(']]>', ']]]]><![CDATA[>', $post->renderedBody()) !!}]]></content:encoded>
                @endif
                @if($image)
                    <media:content url="{{ $image }}" medium="image" />
                @endif
            </item>
        @endforeach
    </channel>
</rss>
