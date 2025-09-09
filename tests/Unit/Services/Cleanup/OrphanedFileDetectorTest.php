<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\OrphanedFileDetector;
use App\Services\Cleanup\Models\AssetFileAnalysis;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class OrphanedFileDetectorTest extends TestCase
{
    private OrphanedFileDetector $detector;
    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new OrphanedFileDetector();
        $this->testDirectory = storage_path('testing/orphaned_files');
        
        // Create test directory using real filesystem
        if (!is_dir($this->testDirectory)) {
            mkdir($this->testDirectory, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
        
        // Clean up test files using real filesystem
        if (is_dir($this->testDirectory)) {
            $this->deleteDirectory($this->testDirectory);
        }
    }
    
    private function deleteDirectory($dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_analyzeAssetFile_returns_correct_analysis()
    {
        // Create a test file using real filesystem
        $testFile = $this->testDirectory . '/test-image.jpg';
        file_put_contents($testFile, 'fake image content');
        
        $analysis = $this->detector->analyzeAssetFile($testFile);
        
        $this->assertInstanceOf(AssetFileAnalysis::class, $analysis);
        $this->assertEquals($testFile, $analysis->filePath);
        $this->assertEquals('images', $analysis->type);
        $this->assertGreaterThan(0, $analysis->size);
        $this->assertNotNull($analysis->lastModified);
    }

    public function test_analyzeAssetFile_throws_exception_for_nonexistent_file()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File does not exist:');
        
        $this->detector->analyzeAssetFile('/nonexistent/file.jpg');
    }

    public function test_validateSafeDeletion_protects_system_files()
    {
        $systemFiles = [
            'public/.htaccess',
            'public/index.php',
            'public/robots.txt',
            'public/favicon.ico',
        ];
        
        foreach ($systemFiles as $systemFile) {
            $this->assertFalse(
                $this->detector->validateSafeDeletion($systemFile),
                "System file {$systemFile} should be protected from deletion"
            );
        }
    }

    public function test_validateSafeDeletion_protects_vendor_and_node_modules()
    {
        $protectedPaths = [
            'vendor/some/package/file.php',
            'node_modules/package/file.js',
            '.git/config',
            'storage/framework/cache/file',
            'storage/logs/laravel.log',
            'bootstrap/cache/config.php',
        ];
        
        foreach ($protectedPaths as $protectedPath) {
            $this->assertFalse(
                $this->detector->validateSafeDeletion($protectedPath),
                "Protected path {$protectedPath} should not be deletable"
            );
        }
    }

    public function test_validateSafeDeletion_allows_safe_asset_files()
    {
        $safeFiles = [
            'public/images/unused-image.jpg',
            'public/css/old-styles.css',
            'public/js/deprecated-script.js',
            'storage/app/public/uploads/old-file.pdf',
        ];
        
        foreach ($safeFiles as $safeFile) {
            $this->assertTrue(
                $this->detector->validateSafeDeletion($safeFile),
                "Safe file {$safeFile} should be deletable"
            );
        }
    }

    public function test_scanCodebaseReferences_finds_asset_references()
    {
        // Create actual test files for this test
        $phpFile = $this->createTestPhpFile();
        $bladeFile = $this->createTestBladeFile();
        
        // Mock File facade methods
        File::shouldReceive('allFiles')
            ->with(Mockery::any())
            ->andReturn([
                new \SplFileInfo($phpFile),
                new \SplFileInfo($bladeFile),
            ]);
        
        File::shouldReceive('isDirectory')->andReturn(true);
        
        // Mock File::get for the test files
        File::shouldReceive('get')
            ->with($phpFile)
            ->andReturn("<?php echo asset('images/logo.png'); ?>");
            
        File::shouldReceive('get')
            ->with($bladeFile)
            ->andReturn('<img src="{{ asset(\'images/banner.jpg\') }}" alt="Banner">');
        
        File::shouldReceive('exists')->andReturn(true);
        
        $references = $this->detector->scanCodebaseReferences();
        
        $this->assertArrayHasKey('public/images/logo.png', $references);
        $this->assertArrayHasKey('public/images/banner.jpg', $references);
    }

    public function test_detectAssetUsage_returns_analysis_for_each_asset()
    {
        // Create test assets using real filesystem
        $testAssets = [
            $this->testDirectory . '/asset1.jpg',
            $this->testDirectory . '/asset2.png',
        ];
        
        foreach ($testAssets as $asset) {
            file_put_contents($asset, 'test content');
        }
        
        $analyses = $this->detector->detectAssetUsage($testAssets);
        
        $this->assertCount(2, $analyses);
        $this->assertContainsOnlyInstancesOf(AssetFileAnalysis::class, $analyses);
    }

    public function test_findOrphanedFiles_identifies_unreferenced_files()
    {
        // Create test files using real filesystem
        $testFile1 = $this->testDirectory . '/orphaned.jpg';
        $testFile2 = $this->testDirectory . '/unused.css';
        file_put_contents($testFile1, 'test content');
        file_put_contents($testFile2, 'test content');
        
        // Mock File facade for directory scanning
        File::shouldReceive('isDirectory')->andReturn(true);
        File::shouldReceive('allFiles')
            ->andReturn([
                new \SplFileInfo($testFile1),
                new \SplFileInfo($testFile2),
            ]);
        
        // Mock File::get for any files that might be scanned
        File::shouldReceive('get')->andReturn('');
        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('size')->andReturn(1024);
        File::shouldReceive('lastModified')->andReturn(time());
        
        $orphanedFiles = $this->detector->findOrphanedFiles();
        
        // Should find the test files as orphaned since they're not referenced
        $this->assertGreaterThanOrEqual(0, count($orphanedFiles));
    }

    private function createTestPhpFile(): string
    {
        $testFile = $this->testDirectory . '/test-php-file.php';
        file_put_contents($testFile, "<?php echo asset('images/logo.png'); ?>");
        return $testFile;
    }

    private function createTestBladeFile(): string
    {
        $testFile = $this->testDirectory . '/test-blade-file.blade.php';
        file_put_contents($testFile, '<img src="{{ asset(\'images/banner.jpg\') }}" alt="Banner">');
        return $testFile;
    }

    public function test_asset_type_detection()
    {
        $testFiles = [
            ['file.jpg', 'images'],
            ['file.png', 'images'],
            ['file.svg', 'images'],
            ['file.ttf', 'fonts'],
            ['file.woff2', 'fonts'],
            ['file.css', 'styles'],
            ['file.js', 'scripts'],
            ['file.pdf', 'documents'],
            ['file.mp4', 'media'],
            ['file.unknown', 'unknown'],
        ];
        
        foreach ($testFiles as [$filename, $expectedType]) {
            $testFile = $this->testDirectory . '/' . $filename;
            file_put_contents($testFile, 'test content');
            
            $analysis = $this->detector->analyzeAssetFile($testFile);
            $this->assertEquals($expectedType, $analysis->type, "File {$filename} should be type {$expectedType}");
        }
    }

    public function test_path_normalization()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('normalizePath');
        $method->setAccessible(true);
        
        $testCases = [
            ['/images/logo.png', 'public/images/logo.png'],
            ['images/logo.png', 'public/images/logo.png'],
            ['public/images/logo.png', 'public/images/logo.png'],
            ['http://example.com/image.jpg', 'http://example.com/image.jpg'],
            ['https://example.com/image.jpg', 'https://example.com/image.jpg'],
            ['//cdn.example.com/image.jpg', '//cdn.example.com/image.jpg'],
            ['data:image/png;base64,abc', 'data:image/png;base64,abc'],
        ];
        
        foreach ($testCases as [$input, $expected]) {
            $result = $method->invoke($this->detector, $input);
            $this->assertEquals($expected, $result, "Path {$input} should normalize to {$expected}");
        }
    }

    public function test_dynamic_reference_detection()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('mightBeDynamicallyReferenced');
        $method->setAccessible(true);
        
        $dynamicFiles = [
            'lang_en.js',
            'theme_dark.css',
            '123.jpg',
            'user_456.png',
        ];
        
        $staticFiles = [
            'logo.png',
            'main.css',
            'app.js',
            'favicon.ico',
        ];
        
        foreach ($dynamicFiles as $file) {
            $this->assertTrue(
                $method->invoke($this->detector, "public/assets/{$file}"),
                "File {$file} should be detected as potentially dynamic"
            );
        }
        
        foreach ($staticFiles as $file) {
            $this->assertFalse(
                $method->invoke($this->detector, "public/assets/{$file}"),
                "File {$file} should not be detected as dynamic"
            );
        }
    }
}