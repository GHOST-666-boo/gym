<?php

namespace Tests\Feature\Services\Cleanup;

use App\Services\Cleanup\PhpAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpAnalyzerIntegrationTest extends TestCase
{
    private PhpAnalyzer $analyzer;
    private string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new PhpAnalyzer();
        $this->testFilesPath = storage_path('app/test_cleanup');

        // Create test files directory
        if (!is_dir($this->testFilesPath)) {
            mkdir($this->testFilesPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testFilesPath)) {
            $files = glob("{$this->testFilesPath}/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testFilesPath);
        }
        parent::tearDown();
    }

    public function test_complete_php_analysis_workflow()
    {
        // Create a realistic Laravel-style codebase structure
        $this->createTestCodebase();

        // Analyze all files
        $analyses = $this->analyzeAllFiles();

        // Test unused imports detection
        $allUnusedImports = [];
        foreach ($analyses as $analysis) {
            $unusedImports = $this->analyzer->findUnusedImports($analysis);
            $allUnusedImports = array_merge($allUnusedImports, $unusedImports);
        }

        $this->assertGreaterThan(0, count($allUnusedImports));

        // Test unused methods detection across files
        $unusedMethods = $this->analyzer->findUnusedMethodsAcrossFiles($analyses);
        $this->assertGreaterThan(0, count($unusedMethods));

        // Test unused variables detection across files
        $unusedVariables = $this->analyzer->findUnusedVariablesAcrossFiles($analyses);
        $this->assertGreaterThan(0, count($unusedVariables));

        // Verify specific expected results
        $unusedMethodNames = array_column($unusedMethods, 'name');
        $this->assertContains('unusedPrivateMethod', $unusedMethodNames);
        $this->assertContains('unusedHelperMethod', $unusedMethodNames);

        $unusedImportNames = array_column($allUnusedImports, 'name');
        $this->assertContains('Carbon\Carbon', $unusedImportNames);
        $this->assertContains('Illuminate\Support\Str', $unusedImportNames);

        $unusedVarNames = array_column($unusedVariables, 'name');
        $this->assertContains('unusedVariable', $unusedVarNames);
    }

    private function createTestCodebase(): void
    {
        // Create base model
        $baseModelCode = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

abstract class BaseModel extends Model
{
    protected function formatDate($date)
    {
        return $date->format("Y-m-d");
    }
    
    private function unusedHelperMethod()
    {
        return "unused";
    }
    
    protected function getTimestamp()
    {
        return time();
    }
}';

        // Create user model
        $userModelCode = '<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Traits\HasUuid;

class User extends BaseModel
{
    use HasUuid;
    
    public function getName(): string
    {
        $unusedVariable = "not used";
        $name = $this->attributes["name"];
        
        return $this->formatDate($this->created_at) . ": " . $name;
    }
    
    private function unusedPrivateMethod()
    {
        return "unused";
    }
    
    protected function getFormattedCreatedAt()
    {
        return $this->getTimestamp();
    }
}';

        // Create service class
        $serviceCode = '<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class UserService
{
    public function getActiveUsers(): Collection
    {
        $users = User::where("active", true)->get();
        $unusedVar = "not used anywhere";
        
        return $users->map(function($user) {
            return $user->getName();
        });
    }
    
    private function unusedServiceMethod()
    {
        $localUnused = "unused";
        return "unused method";
    }
}';

        // Create trait
        $traitCode = '<?php

namespace App\Traits;

trait HasUuid
{
    public function generateUuid(): string
    {
        return uniqid();
    }
    
    private function unusedTraitMethod()
    {
        return "unused";
    }
}';

        file_put_contents("{$this->testFilesPath}/BaseModel.php", $baseModelCode);
        file_put_contents("{$this->testFilesPath}/User.php", $userModelCode);
        file_put_contents("{$this->testFilesPath}/UserService.php", $serviceCode);
        file_put_contents("{$this->testFilesPath}/HasUuid.php", $traitCode);
    }

    private function analyzeAllFiles(): array
    {
        $analyses = [];
        $files = glob("{$this->testFilesPath}/*.php");
        
        foreach ($files as $file) {
            $analyses[] = $this->analyzer->parseFile($file);
        }
        
        return $analyses;
    }
}