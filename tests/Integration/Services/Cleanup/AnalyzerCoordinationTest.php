<?php

namespace Tests\Integration\Services\Cleanup;

use Tests\TestCase;
use App\Services\Cleanup\PhpAnalyzer;
use App\Services\Cleanup\JavaScriptAnalyzer;
use App\Services\Cleanup\BladeAnalyzer;
use App\Services\Cleanup\CssAnalyzer;
use App\Services\Cleanup\LaravelAnalyzer;
use App\Services\Cleanup\OrphanedFileDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

class AnalyzerCoordinationTest extends TestCase
{
    use RefreshDatabase;

    private string $testProjectPath;
    private array $testFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testProjectPath = storage_path('testing/analyzer-coordination');
        $this->createTestFiles();
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testProjectPath)) {
            File::deleteDirectory($this->testProjectPath);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function php_analyzer_integrates_with_other_analyzers()
    {
        // Arrange
        $phpAnalyzer = app(PhpAnalyzer::class);
        $laravelAnalyzer = app(LaravelAnalyzer::class);

        // Act
        $phpAnalysis = $phpAnalyzer->parseFile($this->testFiles['php_controller']);
        $routes = $laravelAnalyzer->parseRouteDefinitions([base_path('routes/web.php')]);

        // Assert
        $this->assertNotNull($phpAnalysis);
        $this->assertIsArray($routes);
        
        // Verify PHP analysis contains expected data
        $this->assertNotEmpty($phpAnalysis->classes);
        $this->assertNotEmpty($phpAnalysis->methods);
    }

    /** @test */
    public function javascript_analyzer_coordinates_with_blade_analyzer()
    {
        // Arrange
        $jsAnalyzer = app(JavaScriptAnalyzer::class);
        $bladeAnalyzer = app(BladeAnalyzer::class);

        // Act
        $jsAnalysis = $jsAnalyzer->parseFile($this->testFiles['js_file']);
        $bladeAnalysis = $bladeAnalyzer->parseTemplate($this->testFiles['blade_file']);

        // Assert
        $this->assertNotNull($jsAnalysis);
        $this->assertNotNull($bladeAnalysis);
        
        // Verify cross-references can be established
        $this->assertIsArray($jsAnalysis->functions);
        $this->assertIsArray($bladeAnalysis->variables);
    }

    /** @test */
    public function css_analyzer_coordinates_with_blade_templates()
    {
        // Arrange
        $cssAnalyzer = app(CssAnalyzer::class);
        $bladeAnalyzer = app(BladeAnalyzer::class);

        // Act
        $cssAnalysis = $cssAnalyzer->parseFile($this->testFiles['css_file']);
        $bladeAnalysis = $bladeAnalyzer->parseTemplate($this->testFiles['blade_file']);

        // Assert
        $this->assertNotNull($cssAnalysis);
        $this->assertNotNull($bladeAnalysis);
        
        // Verify CSS classes can be cross-referenced with Blade usage
        $this->assertIsArray($cssAnalysis->classes);
        $this->assertIsArray($bladeAnalysis->cssClasses);
    }

    /** @test */
    public function orphaned_file_detector_coordinates_with_all_analyzers()
    {
        // Arrange
        $orphanedFileDetector = app(OrphanedFileDetector::class);

        // Act
        $references = $orphanedFileDetector->scanCodebaseReferences();
        $orphanedFiles = $orphanedFileDetector->findOrphanedFiles();

        // Assert
        $this->assertIsArray($references);
        $this->assertIsArray($orphanedFiles);
        
        // Verify detector found references across different file types
        $this->assertNotEmpty($references);
    }

    /** @test */
    public function laravel_analyzer_integrates_with_php_analyzer()
    {
        // Arrange
        $laravelAnalyzer = app(LaravelAnalyzer::class);
        $phpAnalyzer = app(PhpAnalyzer::class);

        // Act
        $controllerMethods = $laravelAnalyzer->analyzeControllerUsage([app_path('Http/Controllers')]);
        $routes = $laravelAnalyzer->parseRouteDefinitions([base_path('routes/web.php')]);

        // Assert
        $this->assertIsArray($controllerMethods);
        $this->assertIsArray($routes);
        
        // Verify Laravel-specific analysis works
        if (!empty($controllerMethods)) {
            $this->assertArrayHasKey('class', $controllerMethods[0]);
            $this->assertArrayHasKey('method', $controllerMethods[0]);
        }
    }

    /** @test */
    public function analyzers_handle_cross_file_dependencies()
    {
        // Arrange
        $phpAnalyzer = app(PhpAnalyzer::class);
        $jsAnalyzer = app(JavaScriptAnalyzer::class);

        // Act
        $phpAnalysis = $phpAnalyzer->parseFile($this->testFiles['php_controller']);
        $jsAnalysis = $jsAnalyzer->parseFile($this->testFiles['js_file']);

        // Assert
        $this->assertNotNull($phpAnalysis);
        $this->assertNotNull($jsAnalysis);
        
        // Verify both analyzers can identify their respective dependencies
        $this->assertIsArray($phpAnalysis->imports);
        $this->assertIsArray($jsAnalysis->imports);
    }

    /** @test */
    public function analyzers_maintain_data_consistency()
    {
        // Arrange
        $analyzers = [
            'php' => app(PhpAnalyzer::class),
            'js' => app(JavaScriptAnalyzer::class),
            'blade' => app(BladeAnalyzer::class),
            'css' => app(CssAnalyzer::class),
        ];

        // Act & Assert
        foreach ($analyzers as $type => $analyzer) {
            $this->assertNotNull($analyzer, "Analyzer for {$type} should be available");
            
            // Verify analyzer has expected interface methods
            $this->assertTrue(method_exists($analyzer, 'parseFile') || method_exists($analyzer, 'parseTemplate'));
        }
    }

    /** @test */
    public function analyzers_handle_error_conditions_gracefully()
    {
        // Arrange
        $phpAnalyzer = app(PhpAnalyzer::class);
        $nonExistentFile = $this->testProjectPath . '/non-existent.php';

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $phpAnalyzer->parseFile($nonExistentFile);
    }

    /** @test */
    public function analyzers_produce_compatible_output_formats()
    {
        // Arrange
        $phpAnalyzer = app(PhpAnalyzer::class);
        $jsAnalyzer = app(JavaScriptAnalyzer::class);

        // Act
        $phpAnalysis = $phpAnalyzer->parseFile($this->testFiles['php_controller']);
        $jsAnalysis = $jsAnalyzer->parseFile($this->testFiles['js_file']);

        // Assert - Verify output formats are compatible for orchestration
        $this->assertObjectHasAttribute('filePath', $phpAnalysis);
        $this->assertObjectHasAttribute('filePath', $jsAnalysis);
        
        $this->assertIsString($phpAnalysis->filePath);
        $this->assertIsString($jsAnalysis->filePath);
    }

    /**
     * Create test files for analyzer coordination testing
     */
    private function createTestFiles(): void
    {
        File::makeDirectory($this->testProjectPath, 0755, true);
        File::makeDirectory($this->testProjectPath . '/app/Http/Controllers', 0755, true);
        File::makeDirectory($this->testProjectPath . '/resources/views', 0755, true);
        File::makeDirectory($this->testProjectPath . '/resources/js', 0755, true);
        File::makeDirectory($this->testProjectPath . '/resources/css', 0755, true);

        // PHP Controller file
        $phpContent = '<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\TestService;

class TestController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view("test.index", compact("users"));
    }
    
    public function show($id)
    {
        $user = User::find($id);
        return view("test.show", compact("user"));
    }
    
    private function unusedMethod()
    {
        return "unused";
    }
}';
        
        $this->testFiles['php_controller'] = $this->testProjectPath . '/app/Http/Controllers/TestController.php';
        File::put($this->testFiles['php_controller'], $phpContent);

        // JavaScript file
        $jsContent = 'import axios from "axios";
import { unused } from "./unused-module";

const API_URL = "/api/test";

function fetchUsers() {
    return axios.get(API_URL + "/users");
}

function fetchUser(id) {
    return axios.get(`${API_URL}/users/${id}`);
}

function unusedFunction() {
    return "This is never called";
}

export { fetchUsers, fetchUser };';
        
        $this->testFiles['js_file'] = $this->testProjectPath . '/resources/js/test.js';
        File::put($this->testFiles['js_file'], $jsContent);

        // Blade template file
        $bladeContent = '@extends("layouts.app")

@section("content")
<div class="container test-container">
    <h1 class="page-title">{{ $title }}</h1>
    
    @foreach($users as $user)
        <div class="user-card">
            <h3 class="user-name">{{ $user->name }}</h3>
            <p class="user-email">{{ $user->email }}</p>
        </div>
    @endforeach
    
    <div class="unused-element">
        This element uses unused CSS classes
    </div>
</div>

<script>
    import { fetchUsers } from "./test.js";
    
    document.addEventListener("DOMContentLoaded", function() {
        fetchUsers().then(users => {
            console.log("Users loaded:", users);
        });
    });
</script>
@endsection';
        
        $this->testFiles['blade_file'] = $this->testProjectPath . '/resources/views/test.blade.php';
        File::put($this->testFiles['blade_file'], $bladeContent);

        // CSS file
        $cssContent = '.container {
    max-width: 1200px;
    margin: 0 auto;
}

.test-container {
    padding: 20px;
}

.page-title {
    font-size: 2rem;
    color: #333;
}

.user-card {
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
}

.user-name {
    font-weight: bold;
}

.user-email {
    color: #666;
}

.unused-class {
    display: none;
}

.another-unused-class {
    color: red;
}';
        
        $this->testFiles['css_file'] = $this->testProjectPath . '/resources/css/test.css';
        File::put($this->testFiles['css_file'], $cssContent);
    }
}