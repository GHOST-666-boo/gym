{!! '<' . '?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Home page -->
    <url>
        <loc>{{ route('home') }}</loc>
        <lastmod>{{ now()->toISOString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    
    <!-- Products index -->
    <url>
        <loc>{{ route('products.index') }}</loc>
        <lastmod>{{ now()->toISOString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    
    <!-- Contact page -->
    <url>
        <loc>{{ route('contact') }}</loc>
        <lastmod>{{ now()->toISOString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    
    <!-- Categories -->
    @foreach($categories as $category)
    <url>
        <loc>{{ route('products.category', $category) }}</loc>
        <lastmod>{{ $category->updated_at->toISOString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
    
    <!-- Products -->
    @foreach($products as $product)
    <url>
        <loc>{{ route('products.show', $product) }}</loc>
        <lastmod>{{ $product->updated_at->toISOString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach
</urlset>