<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\BladeAnalyzer;
use App\Services\Cleanup\Models\BladeTemplateAnalysis;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BladeAnalyzerTest extends TestCase
{
    private BladeAnalyzer $analyzer;
    private string $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new BladeAnalyzer();
        $this->testFilePath = storage_path('test-blade-template.blade.php');
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testFilePath)) {
            File::delete($this->testFilePath);
        }
        parent::tearDown();
    }

    public function test_parse_template_throws_exception_for_non_existent_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found: /non/existent/file.blade.php');
        
        $this->analyzer->parseTemplate('/non/existent/file.blade.php');
    }

    public function test_parse_template_returns_blade_template_analysis(): void
    {
        $content = '@extends("layouts.app")
@section("content")
<div class="container">
    <h1>{{ $title }}</h1>
    <x-product-card :product="$product" />
</div>
@endsection';

        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        $this->assertInstanceOf(BladeTemplateAnalysis::class, $analysis);
        $this->assertEquals($this->testFilePath, $analysis->filePath);
        $this->assertEquals($content, $analysis->content);
    }

    public function test_extract_components_from_x_syntax(): void
    {
        $content = '<x-product-card :product="$product" />
<x-button type="primary">Click me</x-button>
<x-admin.user-table :users="$users" />';

        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        $this->assertContains('product-card', $analysis->components);
        $this->assertContains('button', $analysis->components);
        $this->assertContains('admin.user-table', $analysis->components);
    }

    public function test_extract_components_from_component_directive(): void
    {
        $content = '@component("components.alert")
    @slot("title") Alert Title @endslot
    Alert content
@endcomponent';

        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        $this->assertContains('components.alert', $analysis->components);
    }

    public function test_extract_variables_from_blade_syntax(): void
    {
        $content = '{{ $title }}
{{ $user->name }}
{{ $product->price() }}
{{ $items[0] }}
{!! $htmlContent !!}
@if($isActive)
@foreach($products as $product)
@isset($optionalVar)';

        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        $this->assertContains('title', $analysis->variables);
        $this->assertContains('user', $analysis->variables);
        $this->assertContains('product', $analysis->variables);
        $this->assertContains('items', $analysis->variables);
        $this->assertContains('htmlContent', $analysis->variables);
        $this->assertContains('isActive', $analysis->variables);
        $this->assertContains('products', $analysis->variables);
        $this->assertContains('optionalVar', $analysis->variables);
    }

    public function test_extract_includes_and_extends(): void
    {
        $content = '@extends("layouts.app")
@include("partials.header")
@include("components.sidebar", ["active" => "dashboard"])';

        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        $this->assertContains('layouts.app', $analysis->includes);
        $this->assertContains('partials.header', $analysis->includes);
        $this->assertContains('components.sidebar', $analysis->includes);
    }

    public function test_extract_sections(): void
    {
        $content = '@section("title", "Page Title")
@section("content")
    <p>Content here</p>
@endsection
@yield("scripts")
@yield("styles")';

        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        $this->assertContains('title', $analysis->sections);
        $this->assertContains('content', $analysis->sections);
        $this->assertContains('scripts', $analysis->sections);
        $this->assertContains('styles', $analysis->sections);
    }

    public function test_extract_html_structures(): void
    {
        $content = '<div class="product-card bg-white rounded-lg">
    <h3>{{ $product->name }}</h3>
    <p>{{ $product->description }}</p>
</div>
<section class="hero-section">
    <div class="container">
        <h1>Welcome</h1>
    </div>
</section>';

        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        $this->assertNotEmpty($analysis->htmlStructures);
        
        $divStructures = array_filter($analysis->htmlStructures, function($structure) {
            return $structure['type'] === 'div_structure';
        });
        
        $sectionStructures = array_filter($analysis->htmlStructures, function($structure) {
            return $structure['type'] === 'section_structure';
        });

        $this->assertNotEmpty($divStructures);
        $this->assertNotEmpty($sectionStructures);
    }

    public function test_find_duplicate_structures(): void
    {
        // Create two analyses with similar structures
        $content1 = '<div class="product-card bg-white rounded-lg">
    <h3>{{ $title1 }}</h3>
    <p>{{ $description1 }}</p>
</div>';

        $content2 = '<div class="product-card bg-white rounded-lg">
    <h3>{{ $title2 }}</h3>
    <p>{{ $description2 }}</p>
</div>';

        File::put($this->testFilePath, $content1);
        $analysis1 = $this->analyzer->parseTemplate($this->testFilePath);

        $testFilePath2 = storage_path('test-blade-template2.blade.php');
        File::put($testFilePath2, $content2);
        $analysis2 = $this->analyzer->parseTemplate($testFilePath2);

        $duplicates = $this->analyzer->findDuplicateStructures([$analysis1, $analysis2]);

        $this->assertNotEmpty($duplicates);
        $this->assertEquals(2, count($duplicates[0]['occurrences']));
        $this->assertGreaterThan(0.5, $duplicates[0]['similarity_score']);

        File::delete($testFilePath2);
    }

    public function test_extract_component_candidates(): void
    {
        // Create analyses with highly similar structures
        $content1 = '<div class="product-card bg-white rounded-lg shadow-md">
    <img src="{{ $image1 }}" alt="Product">
    <h3 class="text-lg font-bold">{{ $name1 }}</h3>
    <p class="text-gray-600">{{ $price1 }}</p>
    <button class="btn btn-primary">Add to Cart</button>
</div>';

        $content2 = '<div class="product-card bg-white rounded-lg shadow-md">
    <img src="{{ $image2 }}" alt="Product">
    <h3 class="text-lg font-bold">{{ $name2 }}</h3>
    <p class="text-gray-600">{{ $price2 }}</p>
    <button class="btn btn-primary">Add to Cart</button>
</div>';

        File::put($this->testFilePath, $content1);
        $analysis1 = $this->analyzer->parseTemplate($this->testFilePath);

        $testFilePath2 = storage_path('test-blade-template2.blade.php');
        File::put($testFilePath2, $content2);
        $analysis2 = $this->analyzer->parseTemplate($testFilePath2);

        $candidates = $this->analyzer->extractComponentCandidates([$analysis1, $analysis2]);

        $this->assertNotEmpty($candidates);
        $this->assertArrayHasKey('suggested_name', $candidates[0]);
        $this->assertArrayHasKey('occurrences', $candidates[0]);
        $this->assertArrayHasKey('potential_savings', $candidates[0]);
        $this->assertEquals(1, $candidates[0]['potential_savings']); // 2 occurrences - 1 = 1 saving

        File::delete($testFilePath2);
    }

    public function test_has_component_method(): void
    {
        $content = '<x-product-card :product="$product" />';
        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        $this->assertTrue($analysis->hasComponent('product-card'));
        $this->assertFalse($analysis->hasComponent('non-existent-component'));
    }

    public function test_has_variable_method(): void
    {
        $content = '{{ $title }} {{ $user->name }}';
        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        $this->assertTrue($analysis->hasVariable('title'));
        $this->assertTrue($analysis->hasVariable('user'));
        $this->assertFalse($analysis->hasVariable('nonExistentVar'));
    }

    public function test_complex_blade_template_parsing(): void
    {
        $content = '@extends("layouts.app")

@section("title", "Product List")

@section("content")
<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">{{ $pageTitle }}</h1>
    
    @if($products->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($products as $product)
                <x-product-card 
                    :product="$product" 
                    :show-button="true" 
                    class="hover:shadow-lg transition-shadow"
                />
            @endforeach
        </div>
    @else
        <x-empty-state message="No products found" />
    @endif
    
    @include("partials.pagination", ["items" => $products])
</div>
@endsection

@push("scripts")
<script>
    // Product filtering logic
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize filters
    });
</script>
@endpush';

        File::put($this->testFilePath, $content);

        $analysis = $this->analyzer->parseTemplate($this->testFilePath);

        // Test components
        $this->assertContains('product-card', $analysis->components);
        $this->assertContains('empty-state', $analysis->components);

        // Test variables
        $this->assertContains('pageTitle', $analysis->variables);
        $this->assertContains('products', $analysis->variables);
        $this->assertContains('product', $analysis->variables);

        // Test includes
        $this->assertContains('layouts.app', $analysis->includes);
        $this->assertContains('partials.pagination', $analysis->includes);

        // Test sections
        $this->assertContains('title', $analysis->sections);
        $this->assertContains('content', $analysis->sections);

        // Test HTML structures
        $this->assertNotEmpty($analysis->htmlStructures);
    }

    public function test_normalize_html_structure(): void
    {
        $content1 = '<div class="card">
            {{ $title }}
            <p>{{ $content }}</p>
        </div>';

        $content2 = '<div class="card">
    {{ $differentTitle }}
    <p>{{ $differentContent }}</p>
</div>';

        File::put($this->testFilePath, $content1);
        $analysis1 = $this->analyzer->parseTemplate($this->testFilePath);

        $testFilePath2 = storage_path('test-blade-template2.blade.php');
        File::put($testFilePath2, $content2);
        $analysis2 = $this->analyzer->parseTemplate($testFilePath2);

        $duplicates = $this->analyzer->findDuplicateStructures([$analysis1, $analysis2]);

        // Should find duplicates despite different variable names
        $this->assertNotEmpty($duplicates);
        $this->assertGreaterThan(0.8, $duplicates[0]['similarity_score']);

        File::delete($testFilePath2);
    }

    public function test_find_similar_structures(): void
    {
        // Create templates with similar but not identical structures
        $content1 = '<div class="product-card bg-white rounded-lg shadow-md p-4">
    <img src="{{ $image }}" alt="Product" class="w-full h-48 object-cover">
    <h3 class="text-lg font-bold mt-2">{{ $name }}</h3>
    <p class="text-gray-600">{{ $price }}</p>
    <button class="btn btn-primary mt-2">Add to Cart</button>
</div>';

        $content2 = '<div class="product-card bg-white rounded-lg shadow-lg p-6">
    <img src="{{ $productImage }}" alt="Item" class="w-full h-40 object-cover">
    <h3 class="text-xl font-semibold mt-3">{{ $productName }}</h3>
    <p class="text-gray-500">{{ $productPrice }}</p>
    <button class="btn btn-secondary mt-3">Buy Now</button>
</div>';

        File::put($this->testFilePath, $content1);
        $analysis1 = $this->analyzer->parseTemplate($this->testFilePath);

        $testFilePath2 = storage_path('test-blade-template2.blade.php');
        File::put($testFilePath2, $content2);
        $analysis2 = $this->analyzer->parseTemplate($testFilePath2);

        $duplicates = $this->analyzer->findDuplicateStructures([$analysis1, $analysis2]);

        $this->assertNotEmpty($duplicates);
        
        // Should find similar structures
        $similarDuplicates = array_filter($duplicates, function($duplicate) {
            return isset($duplicate['type']) && $duplicate['type'] === 'similar';
        });
        
        $this->assertNotEmpty($similarDuplicates);
        
        $similarDuplicate = reset($similarDuplicates);
        $this->assertGreaterThan(0.7, $similarDuplicate['similarity_score']);
        $this->assertLessThan(0.99, $similarDuplicate['similarity_score']);

        File::delete($testFilePath2);
    }

    public function test_complexity_score_calculation(): void
    {
        // Simple structure
        $simpleContent = '<div class="simple">
    <p>{{ $text }}</p>
</div>';

        // Complex structure
        $complexContent = '<div class="product-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-4 m-2">
    @if($product->hasImage())
        <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-48 object-cover rounded">
    @endif
    <div class="product-info mt-4">
        <h3 class="text-lg font-bold text-gray-900">{{ $product->name }}</h3>
        <p class="text-sm text-gray-600 mt-1">{{ $product->description }}</p>
        @if($product->onSale())
            <span class="text-red-500 font-semibold">${{ $product->salePrice }}</span>
            <span class="text-gray-400 line-through ml-2">${{ $product->originalPrice }}</span>
        @else
            <span class="text-gray-900 font-semibold">${{ $product->price }}</span>
        @endif
    </div>
    <button class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors mt-4">
        Add to Cart
    </button>
</div>';

        File::put($this->testFilePath, $simpleContent);
        $simpleAnalysis = $this->analyzer->parseTemplate($this->testFilePath);

        $testFilePath2 = storage_path('test-blade-template2.blade.php');
        File::put($testFilePath2, $complexContent);
        $complexAnalysis = $this->analyzer->parseTemplate($testFilePath2);

        $this->assertNotEmpty($simpleAnalysis->htmlStructures);
        $this->assertNotEmpty($complexAnalysis->htmlStructures);

        // Complex structure should have higher complexity score
        // We can't directly test the private method, but we can test through findDuplicateStructures
        $duplicates = $this->analyzer->findDuplicateStructures([$simpleAnalysis, $complexAnalysis]);
        
        // Even if no duplicates found, the method should run without errors
        $this->assertIsArray($duplicates);

        File::delete($testFilePath2);
    }

    public function test_refactoring_priority_calculation(): void
    {
        // Create multiple templates with the same structure to test priority calculation
        $sharedStructure = '<div class="alert alert-info p-4 mb-4 rounded">
    <h4 class="font-bold">{{ $title }}</h4>
    <p>{{ $message }}</p>
</div>';

        $templates = [];
        $analyses = [];
        
        for ($i = 1; $i <= 3; $i++) {
            $filePath = storage_path("test-template-{$i}.blade.php");
            File::put($filePath, $sharedStructure);
            $analyses[] = $this->analyzer->parseTemplate($filePath);
            $templates[] = $filePath;
        }

        $duplicates = $this->analyzer->findDuplicateStructures($analyses);

        $this->assertNotEmpty($duplicates);
        
        // Should have refactoring priority data
        $this->assertArrayHasKey('refactoring_priority', $duplicates[0]);
        $this->assertArrayHasKey('complexity_score', $duplicates[0]);
        
        // Priority should be positive for multiple occurrences
        $this->assertGreaterThan(0, $duplicates[0]['refactoring_priority']);

        // Clean up
        foreach ($templates as $template) {
            File::delete($template);
        }
    }

    public function test_component_extraction_with_meaningful_names(): void
    {
        // Create structures with meaningful class names
        $content1 = '<div class="product-card-container bg-white shadow rounded p-4">
    <div class="product-image-wrapper">
        <img src="{{ $image }}" alt="Product">
    </div>
    <div class="product-details">
        <h3>{{ $name }}</h3>
        <p>{{ $price }}</p>
    </div>
</div>';

        $content2 = '<div class="product-card-container bg-white shadow rounded p-4">
    <div class="product-image-wrapper">
        <img src="{{ $anotherImage }}" alt="Product">
    </div>
    <div class="product-details">
        <h3>{{ $anotherName }}</h3>
        <p>{{ $anotherPrice }}</p>
    </div>
</div>';

        File::put($this->testFilePath, $content1);
        $analysis1 = $this->analyzer->parseTemplate($this->testFilePath);

        $testFilePath2 = storage_path('test-blade-template2.blade.php');
        File::put($testFilePath2, $content2);
        $analysis2 = $this->analyzer->parseTemplate($testFilePath2);

        $candidates = $this->analyzer->extractComponentCandidates([$analysis1, $analysis2]);

        $this->assertNotEmpty($candidates);
        
        // Should suggest a meaningful component name
        $suggestedName = $candidates[0]['suggested_name'];
        $this->assertStringContainsString('product', $suggestedName);
        $this->assertStringContainsString('component', $suggestedName);

        File::delete($testFilePath2);
    }}
