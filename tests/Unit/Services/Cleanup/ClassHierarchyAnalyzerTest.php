<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\ClassHierarchyAnalyzer;
use App\Services\Cleanup\PhpAnalyzer;
use PHPUnit\Framework\TestCase;

class ClassHierarchyAnalyzerTest extends TestCase
{
    private ClassHierarchyAnalyzer $hierarchyAnalyzer;
    private PhpAnalyzer $phpAnalyzer;
    private string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hierarchyAnalyzer = new ClassHierarchyAnalyzer();
        $this->phpAnalyzer = new PhpAnalyzer();
        $this->testFilesPath = __DIR__ . '/test_files';

        // Create test files directory if it doesn't exist
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

    public function test_build_hierarchy_with_inheritance()
    {
        // Create parent class
        $parentCode = '<?php

namespace App\Models;

class BaseModel
{
    protected function parentMethod()
    {
        return "parent";
    }
}';

        // Create child class
        $childCode = '<?php

namespace App\Models;

class User extends BaseModel
{
    public function childMethod()
    {
        return "child";
    }
}';

        $parentFile = "{$this->testFilesPath}/BaseModel.php";
        $childFile = "{$this->testFilesPath}/User.php";
        
        file_put_contents($parentFile, $parentCode);
        file_put_contents($childFile, $childCode);

        $parentAnalysis = $this->phpAnalyzer->parseFile($parentFile);
        $childAnalysis = $this->phpAnalyzer->parseFile($childFile);
        
        $this->hierarchyAnalyzer->buildHierarchy([$parentAnalysis, $childAnalysis]);
        $hierarchy = $this->hierarchyAnalyzer->getHierarchy();

        $this->assertArrayHasKey('App\Models\BaseModel', $hierarchy);
        $this->assertArrayHasKey('App\Models\User', $hierarchy);
        
        $baseModel = $hierarchy['App\Models\BaseModel'];
        $user = $hierarchy['App\Models\User'];
        
        $this->assertNull($baseModel['extends']);
        $this->assertEquals('App\Models\BaseModel', $user['extends']);
        
        $this->assertCount(1, $baseModel['methods']);
        $this->assertCount(1, $user['methods']);
        
        $this->assertEquals('parentMethod', $baseModel['methods'][0]['name']);
        $this->assertEquals('childMethod', $user['methods'][0]['name']);
    }

    public function test_get_parent_classes()
    {
        // Create a three-level hierarchy
        $grandparentCode = '<?php

namespace App\Models;

class GrandParent
{
    public function grandparentMethod() {}
}';

        $parentCode = '<?php

namespace App\Models;

class ParentModel extends GrandParent
{
    public function parentMethod() {}
}';

        $childCode = '<?php

namespace App\Models;

class Child extends ParentModel
{
    public function childMethod() {}
}';

        $grandparentFile = "{$this->testFilesPath}/GrandParent.php";
        $parentFile = "{$this->testFilesPath}/ParentModel.php";
        $childFile = "{$this->testFilesPath}/Child.php";
        
        file_put_contents($grandparentFile, $grandparentCode);
        file_put_contents($parentFile, $parentCode);
        file_put_contents($childFile, $childCode);

        $grandparentAnalysis = $this->phpAnalyzer->parseFile($grandparentFile);
        $parentAnalysis = $this->phpAnalyzer->parseFile($parentFile);
        $childAnalysis = $this->phpAnalyzer->parseFile($childFile);
        
        $this->hierarchyAnalyzer->buildHierarchy([$grandparentAnalysis, $parentAnalysis, $childAnalysis]);
        
        $parentClasses = $this->hierarchyAnalyzer->getParentClasses('App\Models\Child');
        
        $this->assertCount(2, $parentClasses);
        $this->assertContains('App\Models\ParentModel', $parentClasses);
        $this->assertContains('App\Models\GrandParent', $parentClasses);
        
        // Test order - immediate parent should be first
        $this->assertEquals('App\Models\ParentModel', $parentClasses[0]);
        $this->assertEquals('App\Models\GrandParent', $parentClasses[1]);
    }

    public function test_get_child_classes()
    {
        // Create hierarchy with multiple children
        $parentCode = '<?php

namespace App\Models;

class BaseModel
{
    public function baseMethod() {}
}';

        $child1Code = '<?php

namespace App\Models;

class User extends BaseModel
{
    public function userMethod() {}
}';

        $child2Code = '<?php

namespace App\Models;

class Product extends BaseModel
{
    public function productMethod() {}
}';

        $grandchildCode = '<?php

namespace App\Models;

class AdminUser extends User
{
    public function adminMethod() {}
}';

        $parentFile = "{$this->testFilesPath}/BaseModel.php";
        $child1File = "{$this->testFilesPath}/User.php";
        $child2File = "{$this->testFilesPath}/Product.php";
        $grandchildFile = "{$this->testFilesPath}/AdminUser.php";
        
        file_put_contents($parentFile, $parentCode);
        file_put_contents($child1File, $child1Code);
        file_put_contents($child2File, $child2Code);
        file_put_contents($grandchildFile, $grandchildCode);

        $parentAnalysis = $this->phpAnalyzer->parseFile($parentFile);
        $child1Analysis = $this->phpAnalyzer->parseFile($child1File);
        $child2Analysis = $this->phpAnalyzer->parseFile($child2File);
        $grandchildAnalysis = $this->phpAnalyzer->parseFile($grandchildFile);
        
        $this->hierarchyAnalyzer->buildHierarchy([
            $parentAnalysis, 
            $child1Analysis, 
            $child2Analysis, 
            $grandchildAnalysis
        ]);
        
        $childClasses = $this->hierarchyAnalyzer->getChildClasses('App\Models\BaseModel');
        
        $this->assertCount(3, $childClasses);
        $this->assertContains('App\Models\User', $childClasses);
        $this->assertContains('App\Models\Product', $childClasses);
        $this->assertContains('App\Models\AdminUser', $childClasses);
    }

    public function test_is_method_used_with_hierarchy()
    {
        $parentCode = '<?php

namespace App\Models;

class BaseModel
{
    protected function usedMethod()
    {
        return "used";
    }
    
    private function unusedMethod()
    {
        return "unused";
    }
}';

        $childCode = '<?php

namespace App\Models;

class User extends BaseModel
{
    public function publicMethod()
    {
        return $this->usedMethod();
    }
}';

        $parentFile = "{$this->testFilesPath}/BaseModel.php";
        $childFile = "{$this->testFilesPath}/User.php";
        
        file_put_contents($parentFile, $parentCode);
        file_put_contents($childFile, $childCode);

        $parentAnalysis = $this->phpAnalyzer->parseFile($parentFile);
        $childAnalysis = $this->phpAnalyzer->parseFile($childFile);
        
        $this->hierarchyAnalyzer->buildHierarchy([$parentAnalysis, $childAnalysis]);
        $methodCalls = $this->hierarchyAnalyzer->findMethodUsage();
        
        $this->assertTrue(
            $this->hierarchyAnalyzer->isMethodUsed('App\Models\BaseModel', 'usedMethod', $methodCalls)
        );
        
        $this->assertFalse(
            $this->hierarchyAnalyzer->isMethodUsed('App\Models\BaseModel', 'unusedMethod', $methodCalls)
        );
    }

    public function test_find_method_usage_with_static_calls()
    {
        $code = '<?php

namespace App\Services;

class TestService
{
    public static function staticMethod()
    {
        return "static";
    }
    
    public function instanceMethod()
    {
        return self::staticMethod();
    }
}';

        $testFile = "{$this->testFilesPath}/TestService.php";
        file_put_contents($testFile, $code);

        $analysis = $this->phpAnalyzer->parseFile($testFile);
        $this->hierarchyAnalyzer->buildHierarchy([$analysis]);
        
        $methodCalls = $this->hierarchyAnalyzer->findMethodUsage();
        
        $this->assertContains('App\Services\TestService::staticMethod', $methodCalls);
    }

    public function test_find_method_usage_with_constructor_calls()
    {
        $code = '<?php

namespace App\Models;

class User
{
    public function __construct($name)
    {
        $this->name = $name;
    }
}

class UserFactory
{
    public function create()
    {
        return new User("test");
    }
}';

        $testFile = "{$this->testFilesPath}/UserFactory.php";
        file_put_contents($testFile, $code);

        $analysis = $this->phpAnalyzer->parseFile($testFile);
        $this->hierarchyAnalyzer->buildHierarchy([$analysis]);
        
        $methodCalls = $this->hierarchyAnalyzer->findMethodUsage();
        
        $this->assertContains('App\Models\User::__construct', $methodCalls);
    }

    public function test_interface_implementation_detection()
    {
        $interfaceCode = '<?php

namespace App\Contracts;

interface UserRepositoryInterface
{
    public function findById(int $id);
    public function create(array $data);
}';

        $implementationCode = '<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id)
    {
        return null;
    }
    
    public function create(array $data)
    {
        return null;
    }
    
    private function unusedMethod()
    {
        return "unused";
    }
}';

        $interfaceFile = "{$this->testFilesPath}/UserRepositoryInterface.php";
        $implementationFile = "{$this->testFilesPath}/UserRepository.php";
        
        file_put_contents($interfaceFile, $interfaceCode);
        file_put_contents($implementationFile, $implementationCode);

        $interfaceAnalysis = $this->phpAnalyzer->parseFile($interfaceFile);
        $implementationAnalysis = $this->phpAnalyzer->parseFile($implementationFile);
        
        $this->hierarchyAnalyzer->buildHierarchy([$interfaceAnalysis, $implementationAnalysis]);
        $methodCalls = $this->hierarchyAnalyzer->findMethodUsage();
        
        // Interface methods should be considered as used
        $this->assertTrue(
            $this->hierarchyAnalyzer->isMethodUsed('App\Repositories\UserRepository', 'findById', $methodCalls)
        );
        
        $this->assertTrue(
            $this->hierarchyAnalyzer->isMethodUsed('App\Repositories\UserRepository', 'create', $methodCalls)
        );
        
        // Private method should not be used
        $this->assertFalse(
            $this->hierarchyAnalyzer->isMethodUsed('App\Repositories\UserRepository', 'unusedMethod', $methodCalls)
        );
    }
}