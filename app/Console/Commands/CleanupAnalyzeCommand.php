<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\CodebaseAnalysis;

class CleanupAnalyzeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cleanup:analyze 
                            {--path=* : Specific paths to analyze (default: entire codebase)}
                            {--exclude=* : Paths to exclude from analysis}
                            {--types=* : File types to include (php,js,css,blade.php)}
                            {--output= : Output format (table,json,detailed)}
                            {--save= : Save analysis results to file}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze codebase for unused code, duplicates, and optimization opportunities';

    private CleanupOrchestrator $orchestrator;

    public function __construct(CleanupOrchestrator $orchestrator)
    {
        parent::__construct();
        $this->orchestrator = $orchestrator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting codebase analysis...');
        
        try {
            // Create configuration from command options
            $config = $this->createConfigFromOptions();
            
            // Display configuration
            $this->displayConfiguration($config);
            
            // Perform analysis
            $this->info('Analyzing codebase structure and dependencies...');
            $analysis = $this->orchestrator->analyzeCodebase();
            
            // Display results
            $this->displayAnalysisResults($analysis);
            
            // Save results if requested
            if ($this->option('save')) {
                $this->saveAnalysisResults($analysis, $this->option('save'));
            }
            
            $this->info('Analysis completed successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Analysis failed: ' . $e->getMessage());
            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * Create cleanup configuration from command options
     */
    private function createConfigFromOptions(): CleanupConfig
    {
        $config = new CleanupConfig([
            'dryRun' => true, // Analysis is always dry run
            'createBackup' => false, // No backup needed for analysis
            'runTests' => false, // No tests needed for analysis
        ]);

        // Set paths to analyze
        if ($this->option('path')) {
            $config->includePaths = $this->option('path');
        }

        // Set paths to exclude
        if ($this->option('exclude')) {
            $config->excludePaths = array_merge(
                $config->excludePaths,
                $this->option('exclude')
            );
        }

        // Set file types
        if ($this->option('types')) {
            $config->includeFileTypes = $this->option('types');
        }

        return $config;
    }

    /**
     * Display current configuration
     */
    private function displayConfiguration(CleanupConfig $config): void
    {
        $this->info('Analysis Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['File Types', implode(', ', $config->includeFileTypes)],
                ['Excluded Paths', implode(', ', $config->excludePaths) ?: 'None'],
                ['Batch Size', $config->batchSize],
                ['Max File Size', number_format($config->maxFileSize / 1024) . ' KB'],
            ]
        );
        $this->newLine();
    }

    /**
     * Display analysis results
     */
    private function displayAnalysisResults(CodebaseAnalysis $analysis): void
    {
        $outputFormat = $this->option('output') ?: 'table';

        switch ($outputFormat) {
            case 'json':
                $this->displayJsonResults($analysis);
                break;
            case 'detailed':
                $this->displayDetailedResults($analysis);
                break;
            default:
                $this->displayTableResults($analysis);
                break;
        }
    }

    /**
     * Display results in table format
     */
    private function displayTableResults(CodebaseAnalysis $analysis): void
    {
        $this->info('Codebase Analysis Summary:');
        
        // File counts
        $this->table(
            ['File Type', 'Count', 'Total Size (KB)'],
            [
                ['PHP Files', count($analysis->phpFiles), $this->calculateTotalSize($analysis->phpFiles)],
                ['JavaScript Files', count($analysis->jsFiles), $this->calculateTotalSize($analysis->jsFiles)],
                ['CSS Files', count($analysis->cssFiles), $this->calculateTotalSize($analysis->cssFiles)],
                ['Blade Templates', count($analysis->bladeFiles), $this->calculateTotalSize($analysis->bladeFiles)],
                ['Asset Files', count($analysis->assetFiles), $this->calculateTotalSize($analysis->assetFiles)],
            ]
        );

        // Issues summary
        $this->newLine();
        $this->info('Potential Issues Found:');
        
        $unusedCount = $this->countUnusedElements($analysis);
        $duplicateCount = $this->countDuplicates($analysis);
        
        $this->table(
            ['Issue Type', 'Count', 'Estimated Impact'],
            [
                ['Unused Imports', $unusedCount['imports'], 'Low'],
                ['Unused Methods', $unusedCount['methods'], 'Medium'],
                ['Unused Variables', $unusedCount['variables'], 'Low'],
                ['Duplicate Methods', $duplicateCount['methods'], 'High'],
                ['Duplicate Templates', $duplicateCount['templates'], 'Medium'],
                ['Orphaned Files', count($analysis->orphanedFiles ?? []), 'Medium'],
            ]
        );

        // Routes and controllers
        if (!empty($analysis->routeDefinitions)) {
            $this->newLine();
            $this->info('Laravel Components:');
            $this->table(
                ['Component', 'Total', 'Unused'],
                [
                    ['Routes', count($analysis->routeDefinitions), $this->countUnusedRoutes($analysis)],
                    ['Controllers', $this->countControllers($analysis), $this->countUnusedControllers($analysis)],
                    ['Models', count($analysis->modelAnalyses ?? []), $this->countUnusedModels($analysis)],
                ]
            );
        }
    }

    /**
     * Display results in JSON format
     */
    private function displayJsonResults(CodebaseAnalysis $analysis): void
    {
        $results = [
            'summary' => [
                'php_files' => count($analysis->phpFiles),
                'js_files' => count($analysis->jsFiles),
                'css_files' => count($analysis->cssFiles),
                'blade_files' => count($analysis->bladeFiles),
                'asset_files' => count($analysis->assetFiles),
            ],
            'issues' => [
                'unused_elements' => $this->countUnusedElements($analysis),
                'duplicates' => $this->countDuplicates($analysis),
                'orphaned_files' => count($analysis->orphanedFiles ?? []),
            ],
            'laravel_components' => [
                'routes' => count($analysis->routeDefinitions),
                'unused_routes' => $this->countUnusedRoutes($analysis),
            ],
        ];

        $this->line(json_encode($results, JSON_PRETTY_PRINT));
    }

    /**
     * Display detailed results
     */
    private function displayDetailedResults(CodebaseAnalysis $analysis): void
    {
        $this->displayTableResults($analysis);
        
        $this->newLine();
        $this->info('Detailed Analysis:');
        
        // Show specific unused imports
        $this->showUnusedImports($analysis);
        
        // Show duplicate methods
        $this->showDuplicateMethods($analysis);
        
        // Show orphaned files
        $this->showOrphanedFiles($analysis);
    }

    /**
     * Show unused imports details
     */
    private function showUnusedImports(CodebaseAnalysis $analysis): void
    {
        $unusedImports = [];
        
        foreach ($analysis->phpFiles as $file) {
            if (!empty($file->unusedImports)) {
                foreach ($file->unusedImports as $import) {
                    $unusedImports[] = [$file->filePath, $import];
                }
            }
        }
        
        if (!empty($unusedImports)) {
            $this->newLine();
            $this->warn('Unused Imports (showing first 10):');
            $this->table(
                ['File', 'Unused Import'],
                array_slice($unusedImports, 0, 10)
            );
            
            if (count($unusedImports) > 10) {
                $this->info('... and ' . (count($unusedImports) - 10) . ' more');
            }
        }
    }

    /**
     * Show duplicate methods
     */
    private function showDuplicateMethods(CodebaseAnalysis $analysis): void
    {
        $duplicates = [];
        
        foreach ($analysis->phpFiles as $file) {
            if (!empty($file->duplicateMethods)) {
                foreach ($file->duplicateMethods as $method) {
                    $duplicates[] = [$file->filePath, $method['name'], $method['similarity'] . '%'];
                }
            }
        }
        
        if (!empty($duplicates)) {
            $this->newLine();
            $this->warn('Duplicate Methods (showing first 10):');
            $this->table(
                ['File', 'Method', 'Similarity'],
                array_slice($duplicates, 0, 10)
            );
            
            if (count($duplicates) > 10) {
                $this->info('... and ' . (count($duplicates) - 10) . ' more');
            }
        }
    }

    /**
     * Show orphaned files
     */
    private function showOrphanedFiles(CodebaseAnalysis $analysis): void
    {
        if (!empty($analysis->orphanedFiles)) {
            $this->newLine();
            $this->warn('Orphaned Files:');
            
            foreach (array_slice($analysis->orphanedFiles, 0, 10) as $file) {
                $this->line('  - ' . $file);
            }
            
            if (count($analysis->orphanedFiles) > 10) {
                $this->info('... and ' . (count($analysis->orphanedFiles) - 10) . ' more');
            }
        }
    }

    /**
     * Save analysis results to file
     */
    private function saveAnalysisResults(CodebaseAnalysis $analysis, string $filename): void
    {
        $results = [
            'timestamp' => now()->toISOString(),
            'summary' => [
                'php_files' => count($analysis->phpFiles),
                'js_files' => count($analysis->jsFiles),
                'css_files' => count($analysis->cssFiles),
                'blade_files' => count($analysis->bladeFiles),
                'asset_files' => count($analysis->assetFiles),
            ],
            'issues' => [
                'unused_elements' => $this->countUnusedElements($analysis),
                'duplicates' => $this->countDuplicates($analysis),
                'orphaned_files' => $analysis->orphanedFiles ?? [],
            ],
            'detailed_analysis' => $analysis,
        ];

        file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT));
        $this->info("Analysis results saved to: {$filename}");
    }

    /**
     * Calculate total file size
     */
    private function calculateTotalSize(array $files): string
    {
        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += $file->fileSize ?? 0;
        }
        return number_format($totalSize / 1024, 1);
    }

    /**
     * Count unused elements
     */
    private function countUnusedElements(CodebaseAnalysis $analysis): array
    {
        $counts = ['imports' => 0, 'methods' => 0, 'variables' => 0];
        
        foreach ($analysis->phpFiles as $file) {
            $counts['imports'] += count($file->unusedImports ?? []);
            $counts['methods'] += count($file->unusedMethods ?? []);
            $counts['variables'] += count($file->unusedVariables ?? []);
        }
        
        foreach ($analysis->jsFiles as $file) {
            $counts['imports'] += count($file->unusedImports ?? []);
            $counts['variables'] += count($file->unusedVariables ?? []);
        }
        
        return $counts;
    }

    /**
     * Count duplicates
     */
    private function countDuplicates(CodebaseAnalysis $analysis): array
    {
        $counts = ['methods' => 0, 'templates' => 0];
        
        foreach ($analysis->phpFiles as $file) {
            $counts['methods'] += count($file->duplicateMethods ?? []);
        }
        
        foreach ($analysis->bladeFiles as $file) {
            $counts['templates'] += count($file->duplicateStructures ?? []);
        }
        
        return $counts;
    }

    /**
     * Count unused routes
     */
    private function countUnusedRoutes(CodebaseAnalysis $analysis): int
    {
        $unusedCount = 0;
        foreach ($analysis->routeDefinitions as $route) {
            if ($route->isUnused ?? false) {
                $unusedCount++;
            }
        }
        return $unusedCount;
    }

    /**
     * Count controllers
     */
    private function countControllers(CodebaseAnalysis $analysis): int
    {
        $controllers = [];
        foreach ($analysis->phpFiles as $file) {
            if (str_contains($file->filePath, 'Controller')) {
                $controllers[] = $file->filePath;
            }
        }
        return count($controllers);
    }

    /**
     * Count unused controllers
     */
    private function countUnusedControllers(CodebaseAnalysis $analysis): int
    {
        $unusedCount = 0;
        foreach ($analysis->phpFiles as $file) {
            if (str_contains($file->filePath, 'Controller') && ($file->isUnused ?? false)) {
                $unusedCount++;
            }
        }
        return $unusedCount;
    }

    /**
     * Count unused models
     */
    private function countUnusedModels(CodebaseAnalysis $analysis): int
    {
        $unusedCount = 0;
        if (isset($analysis->modelAnalyses)) {
            foreach ($analysis->modelAnalyses as $model) {
                if ($model->isUnused ?? false) {
                    $unusedCount++;
                }
            }
        }
        return $unusedCount;
    }
}