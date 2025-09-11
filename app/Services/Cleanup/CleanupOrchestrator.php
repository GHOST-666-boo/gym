<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\PhpAnalyzerInterface;
use App\Services\Cleanup\Contracts\JavaScriptAnalyzerInterface;
use App\Services\Cleanup\Contracts\BladeAnalyzerInterface;
use App\Services\Cleanup\Contracts\CssAnalyzerInterface;
use App\Services\Cleanup\Contracts\LaravelAnalyzerInterface;
use App\Services\Cleanup\Contracts\OrphanedFileDetectorInterface;
use App\Services\Cleanup\Contracts\FileModificationServiceInterface;
use App\Services\Cleanup\Contracts\CodeRefactoringServiceInterface;
use App\Services\Cleanup\Models\CodebaseAnalysis;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\CleanupReport;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\DependencyGraph;
use App\Services\Cleanup\Models\CleanupMetrics;
use App\Services\Cleanup\Models\OperationLog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanupOrchestrator
{
    public function __construct(
        private PhpAnalyzerInterface $phpAnalyzer,
        private JavaScriptAnalyzerInterface $jsAnalyzer,
        private BladeAnalyzerInterface $bladeAnalyzer,
        private CssAnalyzerInterface $cssAnalyzer,
        private LaravelAnalyzerInterface $laravelAnalyzer,
        private OrphanedFileDetectorInterface $orphanedFileDetector,
        private SafetyValidator $validator,
        private ReportGenerator $reporter,
        private FileModificationServiceInterface $fileModifier,
        private CodeRefactoringServiceInterface $refactoringService,
        private MetricsCollector $metricsCollector,
        private OperationLogger $operationLogger
    ) {}
    
    /**
     * Analyze the entire codebase and coordinate all analyzers
     */
    public function analyzeCodebase(): CodebaseAnalysis
    {
        Log::info('Starting comprehensive codebase analysis');
        $startTime = microtime(true);
        
        try {
            $analysis = new CodebaseAnalysis();
            
            // Analyze PHP files
            Log::info('Analyzing PHP files');
            $analysis->phpFiles = $this->analyzePhpFiles();
            
            // Analyze JavaScript files
            Log::info('Analyzing JavaScript files');
            $analysis->jsFiles = $this->analyzeJavaScriptFiles();
            
            // Analyze Blade templates
            Log::info('Analyzing Blade templates');
            $analysis->bladeFiles = $this->analyzeBladeFiles();
            
            // Analyze CSS files
            Log::info('Analyzing CSS files');
            $analysis->cssFiles = $this->analyzeCssFiles();
            
            // Analyze Laravel-specific components
            Log::info('Analyzing Laravel components');
            $analysis->routeDefinitions = $this->analyzeRoutes();
            
            // Analyze asset files
            Log::info('Analyzing asset files');
            $analysis->assetFiles = $this->analyzeAssetFiles();
            
            // Build dependency graph
            Log::info('Building dependency graph');
            $analysis->dependencies = $this->buildDependencyGraph($analysis);
            
            $executionTime = microtime(true) - $startTime;
            Log::info('Codebase analysis completed', [
                'execution_time' => $executionTime,
                'total_files' => $analysis->getTotalFiles()
            ]);
            
            return $analysis;
            
        } catch (\Exception $e) {
            Log::error('Failed to analyze codebase', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Generate comprehensive cleanup plan based on analysis
     */
    public function generateCleanupPlan(CodebaseAnalysis $analysis): CleanupPlan
    {
        Log::info('Generating comprehensive cleanup plan');
        
        try {
            $plan = new CleanupPlan();
            
            // Find unused imports across all file types
            $plan->importsToRemove = array_merge(
                $this->findUnusedPhpImports($analysis->phpFiles),
                $this->findUnusedJsImports($analysis->jsFiles)
            );
            
            // Find unused methods and functions
            $plan->methodsToRemove = array_merge(
                $this->findUnusedPhpMethods($analysis->phpFiles),
                $this->findUnusedControllerMethods($analysis->routeDefinitions)
            );
            
            // Find unused variables
            $plan->variablesToRemove = array_merge(
                $this->findUnusedPhpVariables($analysis->phpFiles),
                $this->findUnusedJsVariables($analysis->jsFiles),
                $this->findUnusedBladeVariables($analysis->bladeFiles)
            );
            
            // Find duplicate code to refactor
            $plan->duplicatesToRefactor = array_merge(
                $this->findPhpDuplicates($analysis->phpFiles),
                $this->findJsDuplicates($analysis->jsFiles),
                $this->findCssDuplicates($analysis->cssFiles),
                $this->findBladeDuplicates($analysis->bladeFiles)
            );
            
            // Find component extraction opportunities
            $plan->componentsToCreate = $this->findComponentExtractionOpportunities($analysis->bladeFiles);
            
            // Find orphaned files
            $plan->filesToDelete = $this->findOrphanedFiles($analysis);
            
            // Calculate estimated size reduction
            $plan->estimatedSizeReduction = $this->calculateEstimatedSizeReduction($plan);
            
            Log::info('Cleanup plan generated', [
                'total_operations' => $plan->getTotalOperations(),
                'estimated_size_reduction_mb' => $plan->estimatedSizeReduction
            ]);
            
            return $plan;
            
        } catch (\Exception $e) {
            Log::error('Failed to generate cleanup plan', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Execute cleanup operations safely with validation at each step
     */
    public function executeCleanup(CleanupConfig $config): CleanupReport
    {
        Log::info('Starting cleanup execution', ['dry_run' => $config->dryRun]);
        $startTime = microtime(true);
        
        try {
            // Initialize metrics and logging
            $this->metricsCollector->startCollection();
            $this->operationLogger->startLogging();
            
            // Step 1: Analyze codebase
            $analysis = $this->analyzeCodebase();
            
            // Step 2: Generate cleanup plan
            $plan = $this->generateCleanupPlan($analysis);
            
            // Step 3: Validate safety of operations
            if (!$this->validator->validateCleanupSafety($this->planToOperations($plan))) {
                throw new \Exception('Safety validation failed - cleanup aborted');
            }
            
            // Step 4: Create backup if not dry run
            if (!$config->dryRun && $config->createBackup) {
                $this->createBackup();
            }
            
            // Step 5: Execute cleanup operations
            $executionResults = $this->executeCleanupOperations($plan, $config);
            
            // Step 6: Run tests if configured
            if ($config->runTests && !$config->dryRun) {
                if (!$this->validator->runTestValidation()) {
                    Log::warning('Test validation failed after cleanup');
                    $executionResults['test_failures'] = true;
                }
            }
            
            // Step 7: Generate comprehensive report
            $metrics = $this->metricsCollector->getMetrics();
            $operationLog = $this->operationLogger->getLog();
            
            $report = $this->reporter->generateReport($plan, $executionResults, $metrics, $operationLog);
            
            $executionTime = microtime(true) - $startTime;
            Log::info('Cleanup execution completed', [
                'execution_time' => $executionTime,
                'dry_run' => $config->dryRun,
                'total_operations' => $report->getTotalItemsProcessed()
            ]);
            
            return $report;
            
        } catch (\Exception $e) {
            Log::error('Cleanup execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Attempt rollback if not dry run
            if (!$config->dryRun) {
                $this->attemptRollback();
            }
            
            throw $e;
        } finally {
            $this->metricsCollector->stopCollection();
            $this->operationLogger->stopLogging();
        }
    }
    
    /**
     * Analyze PHP files in the application
     */
    private function analyzePhpFiles(): array
    {
        $phpFiles = [];
        $directories = [
            app_path(),
            base_path('routes'),
            base_path('config'),
            base_path('database')
        ];
        
        foreach ($directories as $directory) {
            if (File::isDirectory($directory)) {
                $files = File::allFiles($directory);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'php') {
                        try {
                            $analysis = $this->phpAnalyzer->parseFile($file->getPathname());
                            $phpFiles[] = $analysis;
                        } catch (\Exception $e) {
                            Log::warning('Failed to analyze PHP file', [
                                'file' => $file->getPathname(),
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
        }
        
        return $phpFiles;
    }
    
    /**
     * Analyze JavaScript files in the application
     */
    private function analyzeJavaScriptFiles(): array
    {
        $jsFiles = [];
        $directories = [
            resource_path('js'),
            public_path('js')
        ];
        
        foreach ($directories as $directory) {
            if (File::isDirectory($directory)) {
                $files = File::allFiles($directory);
                foreach ($files as $file) {
                    if (in_array($file->getExtension(), ['js', 'ts', 'jsx', 'tsx'])) {
                        try {
                            $analysis = $this->jsAnalyzer->parseFile($file->getPathname());
                            $jsFiles[] = $analysis;
                        } catch (\Exception $e) {
                            Log::warning('Failed to analyze JavaScript file', [
                                'file' => $file->getPathname(),
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
        }
        
        return $jsFiles;
    }
    
    /**
     * Analyze Blade template files
     */
    private function analyzeBladeFiles(): array
    {
        $bladeFiles = [];
        $viewsDirectory = resource_path('views');
        
        if (File::isDirectory($viewsDirectory)) {
            $files = File::allFiles($viewsDirectory);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    try {
                        $analysis = $this->bladeAnalyzer->parseTemplate($file->getPathname());
                        $bladeFiles[] = $analysis;
                    } catch (\Exception $e) {
                        Log::warning('Failed to analyze Blade file', [
                            'file' => $file->getPathname(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        return $bladeFiles;
    }
    
    /**
     * Analyze CSS files in the application
     */
    private function analyzeCssFiles(): array
    {
        $cssFiles = [];
        $directories = [
            resource_path('css'),
            public_path('css')
        ];
        
        foreach ($directories as $directory) {
            if (File::isDirectory($directory)) {
                $files = File::allFiles($directory);
                foreach ($files as $file) {
                    if (in_array($file->getExtension(), ['css', 'scss', 'sass', 'less'])) {
                        try {
                            $analysis = $this->cssAnalyzer->parseFile($file->getPathname());
                            $cssFiles[] = $analysis;
                        } catch (\Exception $e) {
                            Log::warning('Failed to analyze CSS file', [
                                'file' => $file->getPathname(),
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
        }
        
        return $cssFiles;
    }
    
    /**
     * Analyze Laravel routes
     */
    private function analyzeRoutes(): array
    {
        $routeFiles = [
            base_path('routes/web.php'),
            base_path('routes/api.php')
        ];
        
        return $this->laravelAnalyzer->parseRouteDefinitions($routeFiles);
    }
    
    /**
     * Analyze asset files
     */
    private function analyzeAssetFiles(): array
    {
        $potentialAssetPaths = [
            public_path('images'),
            public_path('fonts'),
            public_path('css'),
            public_path('js'),
            storage_path('app/public')
        ];
        
        // Only include paths that actually exist
        $assetPaths = array_filter($potentialAssetPaths, function($path) {
            return File::isDirectory($path);
        });
        
        return $this->orphanedFileDetector->detectAssetUsage($assetPaths);
    }
    
    /**
     * Build dependency graph between files
     */
    private function buildDependencyGraph(CodebaseAnalysis $analysis): DependencyGraph
    {
        $graph = new DependencyGraph();
        
        // Add dependencies from PHP files
        foreach ($analysis->phpFiles as $phpFile) {
            if (isset($phpFile->imports)) {
                foreach ($phpFile->imports as $import) {
                    $graph->addDependency($phpFile->filePath, $import['class'] ?? '');
                }
            }
        }
        
        // Add dependencies from JavaScript files
        foreach ($analysis->jsFiles as $jsFile) {
            if (isset($jsFile->imports)) {
                foreach ($jsFile->imports as $import) {
                    $graph->addDependency($jsFile->filePath, $import['path'] ?? '');
                }
            }
        }
        
        return $graph;
    }
    
    /**
     * Find unused PHP imports
     */
    private function findUnusedPhpImports(array $phpFiles): array
    {
        $unusedImports = [];
        
        foreach ($phpFiles as $phpFile) {
            $unused = $this->phpAnalyzer->findUnusedImports($phpFile);
            $unusedImports = array_merge($unusedImports, $unused);
        }
        
        return $unusedImports;
    }
    
    /**
     * Find unused JavaScript imports
     */
    private function findUnusedJsImports(array $jsFiles): array
    {
        $unusedImports = [];
        
        foreach ($jsFiles as $jsFile) {
            $unused = $this->jsAnalyzer->findUnusedImports($jsFile);
            $unusedImports = array_merge($unusedImports, $unused);
        }
        
        return $unusedImports;
    }
    
    /**
     * Find unused PHP methods
     */
    private function findUnusedPhpMethods(array $phpFiles): array
    {
        $unusedMethods = [];
        
        foreach ($phpFiles as $phpFile) {
            $unused = $this->phpAnalyzer->findUnusedMethods($phpFile);
            $unusedMethods = array_merge($unusedMethods, $unused);
        }
        
        return $unusedMethods;
    }
    
    /**
     * Find unused controller methods
     */
    private function findUnusedControllerMethods(array $routes): array
    {
        $controllerPaths = [app_path('Http/Controllers')];
        $controllerMethods = $this->laravelAnalyzer->analyzeControllerUsage($controllerPaths);
        
        return $this->laravelAnalyzer->findUnusedControllerMethods($controllerMethods, $routes);
    }
    
    /**
     * Find unused PHP variables
     */
    private function findUnusedPhpVariables(array $phpFiles): array
    {
        // This would be implemented by the PhpAnalyzer
        // For now, return empty array as this is a complex analysis
        return [];
    }
    
    /**
     * Find unused JavaScript variables
     */
    private function findUnusedJsVariables(array $jsFiles): array
    {
        $unusedVariables = [];
        
        foreach ($jsFiles as $jsFile) {
            $unused = $this->jsAnalyzer->findUnusedVariables($jsFile);
            $unusedVariables = array_merge($unusedVariables, $unused);
        }
        
        return $unusedVariables;
    }
    
    /**
     * Find unused Blade variables
     */
    private function findUnusedBladeVariables(array $bladeFiles): array
    {
        // This would require cross-referencing with controller data
        // For now, return empty array
        return [];
    }
    
    /**
     * Find PHP duplicate methods
     */
    private function findPhpDuplicates(array $phpFiles): array
    {
        return $this->phpAnalyzer->findDuplicateMethods($phpFiles);
    }
    
    /**
     * Find JavaScript duplicate functions
     */
    private function findJsDuplicates(array $jsFiles): array
    {
        return $this->jsAnalyzer->findDuplicateFunctions($jsFiles);
    }
    
    /**
     * Find CSS duplicate rules
     */
    private function findCssDuplicates(array $cssFiles): array
    {
        return $this->cssAnalyzer->findDuplicateRules($cssFiles);
    }
    
    /**
     * Find Blade duplicate structures
     */
    private function findBladeDuplicates(array $bladeFiles): array
    {
        return $this->bladeAnalyzer->findDuplicateStructures($bladeFiles);
    }
    
    /**
     * Find component extraction opportunities
     */
    private function findComponentExtractionOpportunities(array $bladeFiles): array
    {
        return $this->bladeAnalyzer->extractComponentCandidates($bladeFiles);
    }
    
    /**
     * Find orphaned files
     */
    private function findOrphanedFiles(CodebaseAnalysis $analysis): array
    {
        $orphanedFiles = $this->orphanedFileDetector->findOrphanedFiles();
        
        // Extract file paths from AssetFileAnalysis objects
        return array_map(function($file) {
            if (is_string($file)) {
                return $file;
            } elseif (isset($file->filePath)) {
                return $file->filePath;
            } elseif (method_exists($file, 'getFilePath')) {
                return $file->getFilePath();
            } else {
                return null;
            }
        }, $orphanedFiles);
    }
    
    /**
     * Calculate estimated size reduction
     */
    private function calculateEstimatedSizeReduction(CleanupPlan $plan): float
    {
        $totalReduction = 0.0;
        
        // Estimate reduction from file deletions
        foreach ($plan->filesToDelete as $file) {
            if (File::exists($file)) {
                $totalReduction += File::size($file);
            }
        }
        
        // Estimate reduction from import removals (average 50 bytes per import)
        $totalReduction += count($plan->importsToRemove) * 50;
        
        // Estimate reduction from method removals (average 500 bytes per method)
        $totalReduction += count($plan->methodsToRemove) * 500;
        
        // Convert to MB
        return $totalReduction / (1024 * 1024);
    }
    
    /**
     * Convert cleanup plan to operations array for validation
     */
    private function planToOperations(CleanupPlan $plan): array
    {
        return [
            'delete_files' => $plan->filesToDelete,
            'remove_imports' => $plan->importsToRemove,
            'remove_methods' => $plan->methodsToRemove,
            'remove_variables' => $plan->variablesToRemove,
            'refactor_duplicates' => $plan->duplicatesToRefactor,
            'create_components' => $plan->componentsToCreate
        ];
    }
    
    /**
     * Execute cleanup operations based on plan and config
     */
    private function executeCleanupOperations(CleanupPlan $plan, CleanupConfig $config): array
    {
        $results = [
            'files_removed' => 0,
            'imports_removed' => 0,
            'methods_removed' => 0,
            'variables_removed' => 0,
            'duplicates_refactored' => 0,
            'components_created' => 0,
            'lines_removed' => 0,
            'failed_operations' => 0
        ];
        
        if ($config->dryRun) {
            Log::info('Dry run mode - no actual changes will be made');
            // In dry run, just count what would be done
            $results['files_removed'] = count($plan->filesToDelete);
            $results['imports_removed'] = count($plan->importsToRemove);
            $results['methods_removed'] = count($plan->methodsToRemove);
            $results['variables_removed'] = count($plan->variablesToRemove);
            $results['duplicates_refactored'] = count($plan->duplicatesToRefactor);
            $results['components_created'] = count($plan->componentsToCreate);
            return $results;
        }
        
        // Execute actual cleanup operations
        try {
            // Remove unused imports
            if ($config->removeUnusedImports) {
                $results['imports_removed'] = $this->executeImportRemovals($plan->importsToRemove);
            }
            
            // Remove unused methods
            if ($config->removeUnusedMethods) {
                $results['methods_removed'] = $this->executeMethodRemovals($plan->methodsToRemove);
            }
            
            // Remove unused variables
            if ($config->removeUnusedVariables) {
                $results['variables_removed'] = $this->executeVariableRemovals($plan->variablesToRemove);
            }
            
            // Refactor duplicates
            if ($config->refactorDuplicates) {
                $results['duplicates_refactored'] = $this->executeDuplicateRefactoring($plan->duplicatesToRefactor);
            }
            
            // Create components
            if ($config->createComponents) {
                $results['components_created'] = $this->executeComponentCreation($plan->componentsToCreate);
            }
            
            // Delete orphaned files (do this last)
            $results['files_removed'] = $this->executeFileDeletions($plan->filesToDelete);
            
        } catch (\Exception $e) {
            Log::error('Error during cleanup execution', [
                'error' => $e->getMessage(),
                'results_so_far' => $results
            ]);
            $results['failed_operations']++;
        }
        
        return $results;
    }
    
    /**
     * Execute import removals
     */
    private function executeImportRemovals(array $imports): int
    {
        $removed = 0;
        foreach ($imports as $import) {
            try {
                if ($this->fileModifier->removeImport($import)) {
                    $removed++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to remove import', ['import' => $import, 'error' => $e->getMessage()]);
            }
        }
        return $removed;
    }
    
    /**
     * Execute method removals
     */
    private function executeMethodRemovals(array $methods): int
    {
        $removed = 0;
        foreach ($methods as $method) {
            try {
                if ($this->fileModifier->removeMethod($method)) {
                    $removed++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to remove method', ['method' => $method, 'error' => $e->getMessage()]);
            }
        }
        return $removed;
    }
    
    /**
     * Execute variable removals
     */
    private function executeVariableRemovals(array $variables): int
    {
        $removed = 0;
        foreach ($variables as $variable) {
            try {
                if ($this->fileModifier->removeVariable($variable)) {
                    $removed++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to remove variable', ['variable' => $variable, 'error' => $e->getMessage()]);
            }
        }
        return $removed;
    }
    
    /**
     * Execute duplicate code refactoring
     */
    private function executeDuplicateRefactoring(array $duplicates): int
    {
        $refactored = 0;
        foreach ($duplicates as $duplicate) {
            try {
                if ($this->refactoringService->refactorDuplicate($duplicate)) {
                    $refactored++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to refactor duplicate', ['duplicate' => $duplicate, 'error' => $e->getMessage()]);
            }
        }
        return $refactored;
    }
    
    /**
     * Execute component creation
     */
    private function executeComponentCreation(array $components): int
    {
        $created = 0;
        foreach ($components as $component) {
            try {
                if ($this->refactoringService->createComponent($component)) {
                    $created++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to create component', ['component' => $component, 'error' => $e->getMessage()]);
            }
        }
        return $created;
    }
    
    /**
     * Execute file deletions
     */
    private function executeFileDeletions(array $files): int
    {
        $deleted = 0;
        foreach ($files as $file) {
            try {
                if (File::exists($file) && $this->orphanedFileDetector->validateSafeDeletion($file)) {
                    File::delete($file);
                    $deleted++;
                    Log::info('Deleted orphaned file', ['file' => $file]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to delete file', ['file' => $file, 'error' => $e->getMessage()]);
            }
        }
        return $deleted;
    }
    
    /**
     * Create backup before cleanup
     */
    private function createBackup(): void
    {
        // This would use GitBackupManager or similar
        Log::info('Creating backup before cleanup operations');
        // Implementation would create git commit or file backup
    }
    
    /**
     * Attempt rollback if cleanup fails
     */
    private function attemptRollback(): void
    {
        Log::warning('Attempting rollback due to cleanup failure');
        // Implementation would restore from backup
    }
}