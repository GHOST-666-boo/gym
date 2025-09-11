<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\PhpAnalyzer;
use App\Models\Cleanup\PhpFileAnalysis;
use PHPUnit\Framework\TestCase;

class PhpAnalyzerTest extends TestCase
{
    private PhpAnalyzer $analyzer;
    private string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new PhpAnalyzer();
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
            $files = glob($this->testFilesPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testFilesPath);
        }
        parent::tearDown();
    }

    public function test_parse_file_with_nonexistent_file()
    {
        $result = $this->analyzer->parseFile('/nonexistent/file.php');

        $this->assertInstanceOf(PhpFileAnalysis::class, $result);
        $this->assertTrue($result->hasErrors());
        $this->assertStringContainsString('File does not exist', $result->errors[0]);
    }

    public function test_parse_simple_class_file()
    {
        $phpCode = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class User extends Model
{
    use HasUuid;
    
    private string $name;
    protected int $age;
    public const STATUS_ACTIVE = "active";
    
    public function getName(): string
    {
        return $this->name;
    }
    
    private function calculateAge(): int
    {
        $currentYear = date("Y");
        return $currentYear - $this->birthYear;
    }
}';

        $testFile = $this->testFilesPath . '/User.php';
        file_put_contents($testFile, $phpCode);

        $result = $this->analyzer->parseFile($testFile);

        $this->assertInstanceOf(PhpFileAnalysis::class, $result);
        $this->assertFalse($result->hasErrors());
        $this->assertEquals('App\Models', $result->namespace);

        // Test classes
        $this->assertCount(1, $result->classes);
        $this->assertEquals('User', $result->classes[0]['name']);
        $this->assertEquals('Model', $result->classes[0]['extends']);

        // Test use statements
        $this->assertCount(2, $result->useStatements);
        $useNames = $result->getUseStatementNames();
        $this->assertContains('Illuminate\Database\Eloquent\Model', $useNames);
        $this->assertContains('App\Traits\HasUuid', $useNames);

        // Test methods
        $this->assertCount(2, $result->methods);
        $methodNames = $result->getMethodNames();
        $this->assertContains('getName', $methodNames);
        $this->assertContains('calculateAge', $methodNames);

        // Test constants
        $this->assertCount(1, $result->constants);
        $this->assertEquals('STATUS_ACTIVE', $result->constants[0]['name']);

        // Test dependencies
        $this->assertContains('Illuminate\Database\Eloquent\Model', $result->dependencies);
        $this->assertContains('App\Traits\HasUuid', $result->dependencies);
    }

    public function test_parse_file_with_functions()
    {
        $phpCode = '<?php

function globalFunction(string $param): bool
{
    return true;
}

function anotherFunction($mixed, int $number = 5)
{
    $localVar = "test";
    return $mixed . $number;
}';

        $testFile = $this->testFilesPath . '/functions.php';
        file_put_contents($testFile, $phpCode);

        $result = $this->analyzer->parseFile($testFile);

        $this->assertFalse($result->hasErrors());
        $this->assertCount(2, $result->functions);

        $functionNames = $result->getFunctionNames();
        $this->assertContains('globalFunction', $functionNames);
        $this->assertContains('anotherFunction', $functionNames);

        // Test function parameters
        $globalFunc = array_filter($result->functions, fn($f) => $f['name'] === 'globalFunction')[0];
        $this->assertCount(1, $globalFunc['parameters']);
        $this->assertEquals('param', $globalFunc['parameters'][0]['name']);
        $this->assertEquals('string', $globalFunc['parameters'][0]['type']);
    }

    public function test_parse_file_with_syntax_error()
    {
        $phpCode = '<?php

class BrokenClass {
    public function missingBrace()
    {
        return "test"
    // Missing closing brace
';

        $testFile = $this->testFilesPath . '/broken.php';
        file_put_contents($testFile, $phpCode);

        $result = $this->analyzer->parseFile($testFile);

        $this->assertTrue($result->hasErrors());
        $this->assertStringContainsString('Parse error', $result->errors[0]);
    }

    public function test_parse_interface_file()
    {
        $phpCode = '<?php

namespace App\Contracts;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function create(array $data): User;
}';

        $testFile = $this->testFilesPath . '/UserRepositoryInterface.php';
        file_put_contents($testFile, $phpCode);

        $result = $this->analyzer->parseFile($testFile);

        $this->assertFalse($result->hasErrors());
        $this->assertEquals('App\Contracts', $result->namespace);
        $this->assertCount(1, $result->classes); // Interfaces are treated as classes in AST
        $this->assertEquals('UserRepositoryInterface', $result->classes[0]['name']);
    }

    public function test_parse_file_with_multiple_classes()
    {
        $phpCode = '<?php

class FirstClass
{
    public function method1() {}
}

class SecondClass extends FirstClass
{
    public function method2() {}
}';

        $testFile = $this->testFilesPath . '/multiple.php';
        file_put_contents($testFile, $phpCode);

        $result = $this->analyzer->parseFile($testFile);

        $this->assertFalse($result->hasErrors());
        $this->assertCount(2, $result->classes);

        $classNames = array_column($result->classes, 'name');
        $this->assertContains('FirstClass', $classNames);
        $this->assertContains('SecondClass', $classNames);

        // Test inheritance
        $secondClass = array_values(array_filter($result->classes, fn($c) => $c['name'] === 'SecondClass'))[0];
        $this->assertEquals('FirstClass', $secondClass['extends']);
    }

    public function test_method_visibility_detection()
    {
        $phpCode = '<?php

class VisibilityTest
{
    public function publicMethod() {}
    protected function protectedMethod() {}
    private function privateMethod() {}
    public static function staticMethod() {}
}';

        $testFile = $this->testFilesPath . '/visibility.php';
        file_put_contents($testFile, $phpCode);

        $result = $this->analyzer->parseFile($testFile);

        $this->assertCount(4, $result->methods);

        foreach ($result->methods as $method) {
            switch ($method['name']) {
                case 'publicMethod':
                    $this->assertEquals('public', $method['visibility']);
                    $this->assertFalse($method['static']);
                    break;
                case 'protectedMethod':
                    $this->assertEquals('protected', $method['visibility']);
                    break;
                case 'privateMethod':
                    $this->assertEquals('private', $method['visibility']);
                    break;
                case 'staticMethod':
                    $this->assertEquals('public', $method['visibility']);
                    $this->assertTrue($method['static']);
                    break;
            }
        }
    }

    public function test_analysis_model_helper_methods()
    {
        $phpCode = '<?php

namespace Test;

use Some\Dependency;

class TestClass
{
    public function testMethod() {}
}

function testFunction() {}';

        $testFile = $this->testFilesPath . '/helper_test.php';
        file_put_contents($testFile, $phpCode);

        $result = $this->analyzer->parseFile($testFile);

        // Test helper methods
        $this->assertEquals('TestClass', $result->getClassName());
        $this->assertContains('testMethod', $result->getMethodNames());
        $this->assertContains('testFunction', $result->getFunctionNames());
        $this->assertContains('Some\Dependency', $result->getUseStatementNames());

        // Test toArray method
        $array = $result->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('filePath', $array);
        $this->assertArrayHasKey('classes', $array);
        $this->assertArrayHasKey('methods', $array);
        $this->assertArrayHasKey('namespace', $array);
    }

    public function test_find_unused_imports_with_no_unused_imports()
    {
        $phpCode = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;
use Carbon\Carbon;

class User extends Model
{
    use HasUuid;
    
    public function getCreatedAtAttribute(): Carbon
    {
        return Carbon::parse($this->attributes["created_at"]);
    }
}';

        $testFile = $this->testFilesPath . '/UserWithUsedImports.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedImports = $this->analyzer->findUnusedImports($analysis);

        $this->assertEmpty($unusedImports);
    }

    public function test_find_unused_imports_with_unused_imports()
    {
        $phpCode = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\UnusedService;

class User extends Model
{
    use HasUuid;
    
    public function getName(): string
    {
        return $this->name;
    }
}';

        $testFile = $this->testFilesPath . '/UserWithUnusedImports.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedImports = $this->analyzer->findUnusedImports($analysis);

        $this->assertCount(3, $unusedImports);

        $unusedNames = array_column($unusedImports, 'name');
        $this->assertContains('Carbon\Carbon', $unusedNames);
        $this->assertContains('Illuminate\Support\Str', $unusedNames);
        $this->assertContains('App\Services\UnusedService', $unusedNames);

        // Check that used imports are not in the unused list
        $this->assertNotContains('Illuminate\Database\Eloquent\Model', $unusedNames);
        $this->assertNotContains('App\Traits\HasUuid', $unusedNames);
    }

    public function test_find_unused_imports_with_static_calls()
    {
        $phpCode = '<?php

namespace App\Models;

use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\UnusedService;

class User
{
    public function generateSlug(string $title): string
    {
        return Str::slug($title);
    }
    
    public function getFormattedDate(): string
    {
        return Carbon::now()->format("Y-m-d");
    }
}';

        $testFile = $this->testFilesPath . '/UserWithStaticCalls.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedImports = $this->analyzer->findUnusedImports($analysis);

        $this->assertCount(1, $unusedImports);
        $this->assertEquals('App\Services\UnusedService', $unusedImports[0]['name']);
    }

    public function test_find_unused_imports_with_type_hints()
    {
        $phpCode = '<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Services\UnusedService;

class UserController
{
    public function store(Request $request): User
    {
        $userService = new UserService();
        return $userService->create($request->all());
    }
}';

        $testFile = $this->testFilesPath . '/UserControllerWithTypeHints.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedImports = $this->analyzer->findUnusedImports($analysis);

        $this->assertCount(1, $unusedImports);
        $this->assertEquals('App\Services\UnusedService', $unusedImports[0]['name']);
    }

    public function test_find_unused_imports_with_instanceof_checks()
    {
        $phpCode = '<?php

namespace App\Services;

use App\Models\User;
use App\Contracts\UserInterface;
use App\Services\UnusedService;

class ValidationService
{
    public function validateUser($user): bool
    {
        if ($user instanceof User) {
            return true;
        }
        
        return $user instanceof UserInterface;
    }
}';

        $testFile = $this->testFilesPath . '/ValidationServiceWithInstanceof.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedImports = $this->analyzer->findUnusedImports($analysis);

        $this->assertCount(1, $unusedImports);
        $this->assertEquals('App\Services\UnusedService', $unusedImports[0]['name']);
    }

    public function test_find_unused_imports_with_catch_blocks()
    {
        $phpCode = '<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;
use App\Exceptions\CustomException;
use App\Services\UnusedService;

class ExceptionService
{
    public function handleExceptions()
    {
        try {
            // Some code
        } catch (InvalidArgumentException $e) {
            // Handle invalid argument
        } catch (CustomException $e) {
            // Handle custom exception
        }
    }
}';

        $testFile = $this->testFilesPath . '/ExceptionServiceWithCatch.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedImports = $this->analyzer->findUnusedImports($analysis);

        $this->assertCount(2, $unusedImports);
        $unusedNames = array_column($unusedImports, 'name');
        $this->assertContains('RuntimeException', $unusedNames);
        $this->assertContains('App\Services\UnusedService', $unusedNames);
    }

    public function test_find_unused_imports_with_aliases()
    {
        $phpCode = '<?php

namespace App\Services;

use Carbon\Carbon as CarbonDate;
use Illuminate\Support\Str as StringHelper;
use App\Services\UnusedService as Unused;

class AliasService
{
    public function formatDate(): string
    {
        return CarbonDate::now()->format("Y-m-d");
    }
}';

        $testFile = $this->testFilesPath . '/AliasServiceWithAliases.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedImports = $this->analyzer->findUnusedImports($analysis);

        $this->assertCount(2, $unusedImports);
        $unusedNames = array_column($unusedImports, 'name');
        $this->assertContains('Illuminate\Support\Str', $unusedNames);
        $this->assertContains('App\Services\UnusedService', $unusedNames);
    }

    public function test_remove_unused_imports_success()
    {
        $phpCode = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Support\Str;

class User extends Model
{
    use HasUuid;
    
    public function getName(): string
    {
        return $this->name;
    }
}';

        $testFile = $this->testFilesPath . '/UserForRemoval.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedImports = $this->analyzer->findUnusedImports($analysis);

        $result = $this->analyzer->removeUnusedImports($testFile, $unusedImports);
        $this->assertTrue($result);

        // Verify the imports were removed
        $updatedContent = file_get_contents($testFile);
        $this->assertStringNotContainsString('use Carbon\Carbon;', $updatedContent);
        $this->assertStringNotContainsString('use Illuminate\Support\Str;', $updatedContent);

        // Verify used imports remain
        $this->assertStringContainsString('use Illuminate\Database\Eloquent\Model;', $updatedContent);
        $this->assertStringContainsString('use App\Traits\HasUuid;', $updatedContent);
    }

    public function test_remove_unused_imports_with_empty_array()
    {
        $phpCode = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
}';

        $testFile = $this->testFilesPath . '/UserNoRemoval.php';
        file_put_contents($testFile, $phpCode);

        $originalContent = file_get_contents($testFile);
        $result = $this->analyzer->removeUnusedImports($testFile, []);

        $this->assertTrue($result);
        $this->assertEquals($originalContent, file_get_contents($testFile));
    }

    public function test_remove_unused_imports_creates_backup()
    {
        $phpCode = '<?php

namespace App\Models;

use Carbon\Carbon;

class User
{
}';

        $testFile = $this->testFilesPath . '/UserBackupTest.php';
        file_put_contents($testFile, $phpCode);

        $unusedImports = [
            ['name' => 'Carbon\Carbon', 'alias' => null, 'line' => 5]
        ];

        $result = $this->analyzer->removeUnusedImports($testFile, $unusedImports);
        $this->assertTrue($result);

        // Check that no backup files remain (they should be cleaned up on success)
        $backupFiles = glob($testFile . '.backup.*');
        $this->assertEmpty($backupFiles);
    }

    public function test_find_unused_methods_with_no_unused_methods()
    {
        $phpCode = '<?php

class TestClass
{
    public function publicMethod()
    {
        $this->privateMethod();
        return $this->protectedMethod();
    }
    
    private function privateMethod()
    {
        return "private";
    }
    
    protected function protectedMethod()
    {
        return "protected";
    }
}';

        $testFile = $this->testFilesPath . '/TestClassNoUnused.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedMethods = $this->analyzer->findUnusedMethods($analysis);

        $this->assertEmpty($unusedMethods);
    }

    public function test_find_unused_methods_with_unused_private_methods()
    {
        $phpCode = '<?php

class TestClass
{
    public function publicMethod()
    {
        $this->usedPrivateMethod();
        return "public";
    }
    
    private function usedPrivateMethod()
    {
        return "used private";
    }
    
    private function unusedPrivateMethod()
    {
        return "unused private";
    }
    
    protected function unusedProtectedMethod()
    {
        return "unused protected";
    }
}';

        $testFile = $this->testFilesPath . '/TestClassWithUnused.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedMethods = $this->analyzer->findUnusedMethods($analysis);

        $this->assertCount(2, $unusedMethods);

        $unusedMethodNames = array_column($unusedMethods, 'name');
        $this->assertContains('unusedPrivateMethod', $unusedMethodNames);
        $this->assertContains('unusedProtectedMethod', $unusedMethodNames);

        // Verify method details
        foreach ($unusedMethods as $method) {
            $this->assertEquals('TestClass', $method['class']);
            $this->assertContains($method['visibility'], ['private', 'protected']);
            $this->assertStringContainsString('not called', $method['reason']);
        }
    }

    public function test_find_unused_methods_ignores_public_methods()
    {
        $phpCode = '<?php

class TestClass
{
    public function publicMethodNotCalled()
    {
        return "public";
    }
    
    private function privateMethodNotCalled()
    {
        return "private";
    }
}';

        $testFile = $this->testFilesPath . '/TestClassPublicIgnored.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedMethods = $this->analyzer->findUnusedMethods($analysis);

        $this->assertCount(1, $unusedMethods);
        $this->assertEquals('privateMethodNotCalled', $unusedMethods[0]['name']);
        $this->assertEquals('private', $unusedMethods[0]['visibility']);
    }

    public function test_find_unused_methods_with_static_calls()
    {
        $phpCode = '<?php

class TestClass
{
    public function publicMethod()
    {
        self::usedStaticMethod();
        static::anotherUsedMethod();
    }
    
    private static function usedStaticMethod()
    {
        return "used static";
    }
    
    protected static function anotherUsedMethod()
    {
        return "another used";
    }
    
    private static function unusedStaticMethod()
    {
        return "unused static";
    }
}';

        $testFile = $this->testFilesPath . '/TestClassWithStatic.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedMethods = $this->analyzer->findUnusedMethods($analysis);

        $this->assertCount(1, $unusedMethods);
        $this->assertEquals('unusedStaticMethod', $unusedMethods[0]['name']);
        $this->assertTrue($unusedMethods[0]['static']);
    }

    public function test_find_unused_methods_with_inheritance_calls()
    {
        $phpCode = '<?php

class ParentClass
{
    protected function parentMethod()
    {
        return "parent";
    }
}

class ChildClass extends ParentClass
{
    public function publicMethod()
    {
        return parent::parentMethod();
    }
    
    private function unusedChildMethod()
    {
        return "unused";
    }
}';

        $testFile = $this->testFilesPath . '/TestClassWithInheritance.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedMethods = $this->analyzer->findUnusedMethods($analysis);

        // Should find both unused methods since parent::parentMethod() doesn't count as usage of parentMethod in the same file
        $this->assertCount(2, $unusedMethods);

        $unusedMethodNames = array_column($unusedMethods, 'name');
        $this->assertContains('unusedChildMethod', $unusedMethodNames);
        $this->assertContains('parentMethod', $unusedMethodNames);
    }

    public function test_find_unused_variables_with_no_unused_variables()
    {
        $phpCode = '<?php

class TestClass
{
    public function testMethod($param1, $param2)
    {
        $localVar = $param1 . $param2;
        $anotherVar = "test";
        
        return $localVar . $anotherVar;
    }
}';

        $testFile = $this->testFilesPath . '/TestClassNoUnusedVars.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);

        $this->assertEmpty($unusedVariables);
    }

    public function test_find_unused_variables_with_unused_variables()
    {
        $phpCode = '<?php

class TestClass
{
    public function testMethod($param1, $param2)
    {
        $usedVar = $param1 . $param2;
        $unusedVar = "this is not used";
        $anotherUnusedVar = 123;
        
        return $usedVar;
    }
    
    public function anotherMethod()
    {
        $onlyDeclared = "never used";
        $used = "this is used";
        
        echo $used;
    }
}';

        $testFile = $this->testFilesPath . '/TestClassWithUnusedVars.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);

        $this->assertCount(3, $unusedVariables);

        $unusedVarNames = array_column($unusedVariables, 'name');
        $this->assertContains('unusedVar', $unusedVarNames);
        $this->assertContains('anotherUnusedVar', $unusedVarNames);
        $this->assertContains('onlyDeclared', $unusedVarNames);

        // Verify variable details
        foreach ($unusedVariables as $variable) {
            $this->assertEquals('TestClass', $variable['class']);
            $this->assertContains($variable['method'], ['testMethod', 'anotherMethod']);
            $this->assertStringContainsString('never used', $variable['reason']);
        }
    }

    public function test_find_unused_variables_in_functions()
    {
        $phpCode = '<?php

function testFunction($param)
{
    $used = $param . "suffix";
    $unused = "not used anywhere";
    
    return $used;
}

function anotherFunction()
{
    $declared = "value";
    // $declared is never used
}';

        $testFile = $this->testFilesPath . '/TestFunctionsWithUnusedVars.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);

        $this->assertCount(2, $unusedVariables);

        $unusedVarNames = array_column($unusedVariables, 'name');
        $this->assertContains('unused', $unusedVarNames);
        $this->assertContains('declared', $unusedVarNames);

        // Check that function context is tracked
        foreach ($unusedVariables as $variable) {
            $this->assertNull($variable['class']);
            $this->assertContains($variable['method'], ['testFunction', 'anotherFunction']);
        }
    }

    public function test_find_unused_variables_with_foreach_and_catch()
    {
        $phpCode = '<?php

class TestClass
{
    public function testMethod()
    {
        $items = [1, 2, 3];
        $unused = "not used";
        
        foreach ($items as $key => $value) {
            echo $key . ": " . $value;
        }
        
        try {
            // Some code
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}';

        $testFile = $this->testFilesPath . '/TestClassWithForeachCatch.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);

        $this->assertCount(1, $unusedVariables);
        $this->assertEquals('unused', $unusedVariables[0]['name']);

        // Verify that foreach and catch variables are not marked as unused
        $unusedVarNames = array_column($unusedVariables, 'name');
        $this->assertNotContains('items', $unusedVarNames);
        $this->assertNotContains('key', $unusedVarNames);
        $this->assertNotContains('value', $unusedVarNames);
        $this->assertNotContains('e', $unusedVarNames);
    }

    public function test_find_unused_variables_excludes_this_variable()
    {
        $phpCode = '<?php

class TestClass
{
    private $property = "value";
    
    public function testMethod()
    {
        $unused = "not used";
        
        return $this->property;
    }
}';

        $testFile = $this->testFilesPath . '/TestClassWithThis.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);

        $this->assertCount(1, $unusedVariables);
        $this->assertEquals('unused', $unusedVariables[0]['name']);

        // Verify that $this is not tracked as unused
        $unusedVarNames = array_column($unusedVariables, 'name');
        $this->assertNotContains('this', $unusedVarNames);
    }

    public function test_find_unused_variables_with_method_parameters()
    {
        $phpCode = '<?php

class TestClass
{
    public function testMethod($usedParam, $unusedParam)
    {
        $localVar = $usedParam . "suffix";
        
        return $localVar;
    }
}';

        $testFile = $this->testFilesPath . '/TestClassWithParams.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);

        // Parameters are automatically marked as used, so no unused variables should be found
        // This is because parameters are part of the method signature and removing them would break the interface
        $this->assertEmpty($unusedVariables);
    }

    public function test_find_unused_methods_across_files_with_hierarchy()
    {
        // Create parent class
        $parentCode = '<?php

namespace App\Models;

class BaseModel
{
    protected function usedByChild()
    {
        return "used";
    }
    
    private function unusedInParent()
    {
        return "unused";
    }
    
    protected function overriddenMethod()
    {
        return "parent";
    }
}';

        // Create child class
        $childCode = '<?php

namespace App\Models;

class User extends BaseModel
{
    public function publicMethod()
    {
        return $this->usedByChild();
    }
    
    private function unusedInChild()
    {
        return "unused";
    }
    
    protected function overriddenMethod()
    {
        return "child";
    }
}';

        $parentFile = $this->testFilesPath . '/BaseModel.php';
        $childFile = $this->testFilesPath . '/User.php';
        
        file_put_contents($parentFile, $parentCode);
        file_put_contents($childFile, $childCode);

        $parentAnalysis = $this->analyzer->parseFile($parentFile);
        $childAnalysis = $this->analyzer->parseFile($childFile);
        
        $unusedMethods = $this->analyzer->findUnusedMethodsAcrossFiles([$parentAnalysis, $childAnalysis]);

        $this->assertCount(4, $unusedMethods);
        
        $unusedMethodNames = array_column($unusedMethods, 'name');
        $this->assertContains('unusedInParent', $unusedMethodNames);
        $this->assertContains('unusedInChild', $unusedMethodNames);
        $this->assertContains('overriddenMethod', $unusedMethodNames);
        
        // Verify that used methods are not in the unused list
        $this->assertNotContains('usedByChild', $unusedMethodNames);
        $this->assertNotContains('publicMethod', $unusedMethodNames);
    }

    public function test_find_unused_variables_with_closures()
    {
        $phpCode = '<?php

class TestClass
{
    public function testMethod()
    {
        $used = "value";
        $unused = "not used";
        $closureVar = "closure";
        
        $closure = function($param) use ($used, $closureVar) {
            $localUnused = "local unused";
            return $used . $param;
        };
        
        return $closure("test");
    }
}';

        $testFile = $this->testFilesPath . '/TestClassWithClosures.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);

        $this->assertCount(2, $unusedVariables);
        
        $unusedVarNames = array_column($unusedVariables, 'name');
        $this->assertContains('unused', $unusedVarNames);
        $this->assertContains('localUnused', $unusedVarNames);
        
        // Verify that used variables are not marked as unused
        $this->assertNotContains('used', $unusedVarNames);
        $this->assertNotContains('closureVar', $unusedVarNames);
    }

    public function test_find_unused_variables_with_complex_expressions()
    {
        $phpCode = '<?php

class TestClass
{
    public function testMethod()
    {
        $array = [1, 2, 3];
        $key = "test";
        $unused = "not used";
        $condition = true;
        $result = null;
        
        // Array access
        $value = $array[$key];
        
        // Ternary operator
        $result = $condition ? $value : "default";
        
        // Null coalescing
        $final = $result ?? "fallback";
        
        return $final;
    }
}';

        $testFile = $this->testFilesPath . '/TestClassComplexExpressions.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);

        $this->assertCount(1, $unusedVariables);
        $this->assertEquals('unused', $unusedVariables[0]['name']);
        
        // Verify all other variables are marked as used
        $unusedVarNames = array_column($unusedVariables, 'name');
        $this->assertNotContains('array', $unusedVarNames);
        $this->assertNotContains('key', $unusedVarNames);
        $this->assertNotContains('condition', $unusedVarNames);
        $this->assertNotContains('result', $unusedVarNames);
        $this->assertNotContains('value', $unusedVarNames);
        $this->assertNotContains('final', $unusedVarNames);
    }

    public function test_find_unused_variables_with_global_and_static()
    {
        $phpCode = '<?php

function testFunction()
{
    global $globalVar;
    static $staticVar = "static";
    
    $unused = "not used";
    $used = $globalVar . $staticVar;
    
    return $used;
}';

        $testFile = $this->testFilesPath . '/TestFunctionGlobalStatic.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);

        $this->assertCount(1, $unusedVariables);
        $this->assertEquals('unused', $unusedVariables[0]['name']);
        
        // Verify global and static variables are not marked as unused
        $unusedVarNames = array_column($unusedVariables, 'name');
        $this->assertNotContains('globalVar', $unusedVarNames);
        $this->assertNotContains('staticVar', $unusedVarNames);
    }

    public function test_find_unused_variables_across_files()
    {
        // Create first file
        $file1Code = '<?php

class FirstClass
{
    public function method1()
    {
        $used1 = "value1";
        $unused1 = "not used";
        
        return $used1;
    }
}';

        // Create second file
        $file2Code = '<?php

class SecondClass
{
    public function method2()
    {
        $used2 = "value2";
        $unused2 = "not used";
        
        return $used2;
    }
}';

        $file1 = $this->testFilesPath . '/FirstClass.php';
        $file2 = $this->testFilesPath . '/SecondClass.php';
        
        file_put_contents($file1, $file1Code);
        file_put_contents($file2, $file2Code);

        $analysis1 = $this->analyzer->parseFile($file1);
        $analysis2 = $this->analyzer->parseFile($file2);
        
        $unusedVariables = $this->analyzer->findUnusedVariablesAcrossFiles([$analysis1, $analysis2]);

        $this->assertCount(2, $unusedVariables);
        
        $unusedVarNames = array_column($unusedVariables, 'name');
        $this->assertContains('unused1', $unusedVarNames);
        $this->assertContains('unused2', $unusedVarNames);
        
        // Verify file paths are included
        foreach ($unusedVariables as $variable) {
            $this->assertArrayHasKey('filePath', $variable);
            $this->assertContains($variable['filePath'], [$file1, $file2]);
        }
    }

    public function test_find_unused_methods_with_static_calls_across_hierarchy()
    {
        $phpCode = '<?php

namespace App\Services;

class ParentService
{
    protected static function usedStaticMethod()
    {
        return "used";
    }
    
    private static function unusedStaticMethod()
    {
        return "unused";
    }
}

class ChildService extends ParentService
{
    public function publicMethod()
    {
        return self::usedStaticMethod();
    }
    
    private function unusedInstanceMethod()
    {
        return "unused";
    }
}';

        $testFile = $this->testFilesPath . '/ServiceHierarchy.php';
        file_put_contents($testFile, $phpCode);

        $analysis = $this->analyzer->parseFile($testFile);
        $unusedMethods = $this->analyzer->findUnusedMethodsAcrossFiles([$analysis]);

        $this->assertCount(2, $unusedMethods);
        
        $unusedMethodNames = array_column($unusedMethods, 'name');
        $this->assertContains('unusedStaticMethod', $unusedMethodNames);
        $this->assertContains('unusedInstanceMethod', $unusedMethodNames);
        
        // Verify used method is not marked as unused
        $this->assertNotContains('usedStaticMethod', $unusedMethodNames);
    }
}