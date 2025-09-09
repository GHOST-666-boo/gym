<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\CrossFileDuplicateDetector;
use App\Services\Cleanup\Contracts\JavaScriptAnalyzerInterface;
use App\Services\Cleanup\Contracts\CssAnalyzerInterface;
use App\Services\Cleanup\Contracts\BladeAnalyzerInterface;
use App\Services\Cleanup\Models\CrossFileDuplicateReport;
use App\Services\Cleanup\Models\ComponentExtractionSuggestion;
use PHPUnit\Framework\TestCase;
use Mockery;

class CrossFileDuplicateDetectorTest extends TestCase
{
    private CrossFileDuplicateDetector $detector;
    private $jsAnalyzer;
    private $cssAnalyzer;
    private $bladeAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->jsAnalyzer = Mockery::mock(JavaScriptAnalyzerInterface::class);
        $this->cssAnalyzer = Mockery::mock(CssAnalyzerInterface::class);
        $this->bladeAnalyzer = Mockery::mock(BladeAnalyzerInterface::class);
        
        $this->detector = new CrossFileDuplicateDetector(
            $this->jsAnalyzer,
            $this->cssAnalyzer,
            $this->bladeAnalyzer
        );
    }

    public function test_finds_javascript_duplicates()
    {
        $jsAnalyses = []; // Mock JS analyses
        
        $mockJsDuplicates = [
            [
                'signature' => 'validateEmail(email)',
                'occurrences' => [
                    [
                        'file' => '/js/auth.js',
                        'function' => [
                            'name' => 'validateEmail',
                            'body' => 'function validateEmail(email) { return /\S+@\S+\.\S+/.test(email); }',
                            'params' => [['name' => 'email']]
                        ]
                    ],
                    [
                        'file' => '/js/contact.js',
                        'function' => [
                            'name' => 'validateEmail',
                            'body' => 'function validateEmail(email) { return /\S+@\S+\.\S+/.test(email); }',
                            'params' => [['name' => 'email']]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->jsAnalyzer
            ->shouldReceive('findDuplicateFunctions')
            ->with($jsAnalyses)
            ->andReturn($mockJsDuplicates);
        
        $this->cssAnalyzer
            ->shouldReceive('findDuplicateRules')
            ->andReturn([]);
        
        $this->bladeAnalyzer
            ->shouldReceive('findDuplicateStructures')
            ->andReturn([]);
        
        $this->bladeAnalyzer
            ->shouldReceive('extractComponentCandidates')
            ->andReturn([]);
        
        $result = $this->detector->findAllDuplicates($jsAnalyses, [], []);
        
        $this->assertInstanceOf(CrossFileDuplicateReport::class, $result);
        $this->assertCount(1, $result->jsDuplicates);
        $this->assertEquals('javascript_function', $result->jsDuplicates[0]['type']);
        $this->assertEquals('validateEmail(email)', $result->jsDuplicates[0]['signature']);
    }

    public function test_finds_css_duplicates()
    {
        $cssAnalyses = []; // Mock CSS analyses
        
        $mockCssDuplicates = [
            [
                'signature' => 'abc123',
                'occurrences' => [
                    [
                        'file' => '/css/main.css',
                        'rule' => [
                            'selector' => '.button',
                            'properties' => 'padding: 10px; background: blue; color: white;'
                        ]
                    ],
                    [
                        'file' => '/css/components.css',
                        'rule' => [
                            'selector' => '.btn',
                            'properties' => 'padding: 10px; background: blue; color: white;'
                        ]
                    ]
                ]
            ]
        ];
        
        $this->jsAnalyzer
            ->shouldReceive('findDuplicateFunctions')
            ->andReturn([]);
        
        $this->cssAnalyzer
            ->shouldReceive('findDuplicateRules')
            ->with($cssAnalyses)
            ->andReturn($mockCssDuplicates);
        
        $this->bladeAnalyzer
            ->shouldReceive('findDuplicateStructures')
            ->andReturn([]);
        
        $this->bladeAnalyzer
            ->shouldReceive('extractComponentCandidates')
            ->andReturn([]);
        
        $result = $this->detector->findAllDuplicates([], $cssAnalyses, []);
        
        $this->assertInstanceOf(CrossFileDuplicateReport::class, $result);
        $this->assertCount(1, $result->cssDuplicates);
        $this->assertEquals('css_rule', $result->cssDuplicates[0]['type']);
    }

    public function test_finds_blade_duplicates()
    {
        $bladeAnalyses = []; // Mock Blade analyses
        
        $mockBladeDuplicates = [
            [
                'hash' => 'def456',
                'occurrences' => [
                    [
                        'file' => '/views/home.blade.php',
                        'structure' => [
                            'type' => 'div_structure',
                            'content' => '<div class="card"><h3>{{VAR}}</h3><p>{{VAR}}</p></div>',
                            'size' => 50
                        ]
                    ],
                    [
                        'file' => '/views/about.blade.php',
                        'structure' => [
                            'type' => 'div_structure',
                            'content' => '<div class="card"><h3>{{VAR}}</h3><p>{{VAR}}</p></div>',
                            'size' => 50
                        ]
                    ]
                ],
                'similarity_score' => 0.95,
                'complexity_score' => 15,
                'refactoring_priority' => 30
            ]
        ];
        
        $this->jsAnalyzer
            ->shouldReceive('findDuplicateFunctions')
            ->andReturn([]);
        
        $this->cssAnalyzer
            ->shouldReceive('findDuplicateRules')
            ->andReturn([]);
        
        $this->bladeAnalyzer
            ->shouldReceive('findDuplicateStructures')
            ->with($bladeAnalyses)
            ->andReturn($mockBladeDuplicates);
        
        $this->bladeAnalyzer
            ->shouldReceive('extractComponentCandidates')
            ->andReturn([]);
        
        $result = $this->detector->findAllDuplicates([], [], $bladeAnalyses);
        
        $this->assertInstanceOf(CrossFileDuplicateReport::class, $result);
        $this->assertCount(1, $result->bladeDuplicates);
        $this->assertEquals('blade_structure', $result->bladeDuplicates[0]['type']);
        $this->assertEquals(0.95, $result->bladeDuplicates[0]['similarity_score']);
    }

    public function test_generates_component_suggestions()
    {
        $bladeAnalyses = []; // Mock Blade analyses
        
        $mockComponentCandidates = [
            [
                'suggested_name' => 'product-card',
                'occurrences' => [
                    ['file' => '/views/products.blade.php'],
                    ['file' => '/views/catalog.blade.php'],
                    ['file' => '/views/search.blade.php']
                ],
                'potential_savings' => 2,
                'structure' => [
                    'content' => '<div class="product-card">...</div>',
                    'type' => 'div_structure'
                ]
            ]
        ];
        
        $this->jsAnalyzer
            ->shouldReceive('findDuplicateFunctions')
            ->andReturn([]);
        
        $this->cssAnalyzer
            ->shouldReceive('findDuplicateRules')
            ->andReturn([]);
        
        $this->bladeAnalyzer
            ->shouldReceive('findDuplicateStructures')
            ->andReturn([]);
        
        $this->bladeAnalyzer
            ->shouldReceive('extractComponentCandidates')
            ->with($bladeAnalyses)
            ->andReturn($mockComponentCandidates);
        
        $result = $this->detector->findAllDuplicates([], [], $bladeAnalyses);
        
        $this->assertInstanceOf(CrossFileDuplicateReport::class, $result);
        $this->assertCount(1, $result->componentSuggestions);
        $this->assertInstanceOf(ComponentExtractionSuggestion::class, $result->componentSuggestions[0]);
        $this->assertEquals('product-card', $result->componentSuggestions[0]->suggestedName);
    }

    public function test_generates_comprehensive_summary()
    {
        $mockJsDuplicates = [
            [
                'signature' => 'test()',
                'occurrences' => [
                    ['file' => '/js/file1.js', 'function' => ['body' => 'function test() { return true; }']],
                    ['file' => '/js/file2.js', 'function' => ['body' => 'function test() { return true; }']]
                ]
            ]
        ];
        
        $mockCssDuplicates = [
            [
                'signature' => 'css123',
                'occurrences' => [
                    ['file' => '/css/file1.css', 'rule' => ['properties' => 'color: red;']],
                    ['file' => '/css/file2.css', 'rule' => ['properties' => 'color: red;']]
                ]
            ]
        ];
        
        $mockBladeDuplicates = [
            [
                'hash' => 'blade123',
                'occurrences' => [
                    ['file' => '/views/file1.blade.php', 'structure' => ['content' => '<div>test</div>', 'size' => 20]],
                    ['file' => '/views/file2.blade.php', 'structure' => ['content' => '<div>test</div>', 'size' => 20]]
                ],
                'similarity_score' => 0.9,
                'complexity_score' => 10,
                'refactoring_priority' => 20
            ]
        ];
        
        $mockComponentCandidates = [
            [
                'suggested_name' => 'test-component',
                'occurrences' => [['file' => '/views/test.blade.php']],
                'potential_savings' => 1,
                'structure' => ['content' => '<div>component</div>']
            ]
        ];
        
        $this->jsAnalyzer
            ->shouldReceive('findDuplicateFunctions')
            ->andReturn($mockJsDuplicates);
        
        $this->cssAnalyzer
            ->shouldReceive('findDuplicateRules')
            ->andReturn($mockCssDuplicates);
        
        $this->bladeAnalyzer
            ->shouldReceive('findDuplicateStructures')
            ->andReturn($mockBladeDuplicates);
        
        $this->bladeAnalyzer
            ->shouldReceive('extractComponentCandidates')
            ->andReturn($mockComponentCandidates);
        
        $result = $this->detector->findAllDuplicates([], [], []);
        
        $this->assertEquals(3, $result->summary['total_duplicates_found']);
        $this->assertEquals(1, $result->summary['javascript_duplicates']);
        $this->assertEquals(1, $result->summary['css_duplicates']);
        $this->assertEquals(1, $result->summary['blade_duplicates']);
        $this->assertEquals(1, $result->summary['component_suggestions']);
        
        $this->assertArrayHasKey('estimated_savings', $result->summary);
        $this->assertArrayHasKey('priority_recommendations', $result->summary);
    }

    public function test_calculates_javascript_complexity_correctly()
    {
        $jsAnalyses = [];
        
        $complexFunction = [
            'signature' => 'complexFunction()',
            'occurrences' => [
                [
                    'file' => '/js/complex.js',
                    'function' => [
                        'name' => 'complexFunction',
                        'body' => 'function complexFunction() { if (true) { for (let i = 0; i < 10; i++) { if (i % 2 === 0) { console.log(i); } } } }',
                        'params' => []
                    ]
                ]
            ]
        ];
        
        $this->jsAnalyzer
            ->shouldReceive('findDuplicateFunctions')
            ->andReturn([$complexFunction]);
        
        $this->cssAnalyzer->shouldReceive('findDuplicateRules')->andReturn([]);
        $this->bladeAnalyzer->shouldReceive('findDuplicateStructures')->andReturn([]);
        $this->bladeAnalyzer->shouldReceive('extractComponentCandidates')->andReturn([]);
        
        $result = $this->detector->findAllDuplicates($jsAnalyses, [], []);
        
        $this->assertGreaterThan(1, $result->jsDuplicates[0]['complexity']);
        $this->assertContains($result->jsDuplicates[0]['effort'], ['low', 'medium', 'high']);
    }

    public function test_prioritizes_duplicates_correctly()
    {
        $mockJsDuplicates = [
            [
                'signature' => 'highPriority()',
                'occurrences' => array_fill(0, 5, [
                    'file' => '/js/test.js',
                    'function' => ['body' => 'function highPriority() { /* complex logic */ }']
                ])
            ]
        ];
        
        $this->jsAnalyzer
            ->shouldReceive('findDuplicateFunctions')
            ->andReturn($mockJsDuplicates);
        
        $this->cssAnalyzer->shouldReceive('findDuplicateRules')->andReturn([]);
        $this->bladeAnalyzer->shouldReceive('findDuplicateStructures')->andReturn([]);
        $this->bladeAnalyzer->shouldReceive('extractComponentCandidates')->andReturn([]);
        
        $result = $this->detector->findAllDuplicates([], [], []);
        
        $recommendations = $result->summary['priority_recommendations'];
        $this->assertNotEmpty($recommendations);
        
        // Check that recommendations are sorted by priority (highest first)
        for ($i = 0; $i < count($recommendations) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $recommendations[$i + 1]['priority'],
                $recommendations[$i]['priority']
            );
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}