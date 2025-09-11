<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\CodebaseAnalysis;

class CleanupSelectiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cleanup:selective 
                            {--type=* : File types to clean (php,js,css,blade)}
                            {--directory=* : Specific directories to clean}
                            {--operation=* : Specific operations (imports,methods,variables,duplicates,components)}
                            {--dry-run : Preview changes without executing them}
                            {--interactive : Interactive selection mode}';

    /**
     * The console command description.
     */
    protected $description = 'Selective cleanup for specific file types, directories, or operations';

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
        $this->info('🎯 Selective Cleanup Tool');
        $this->newLine();

        try {
            if ($this->option('interactive')) {
                return $this->handleInteractiveMode();
            } else {
                return $this->handleDirectMode();
            }
        } catch (\Exception $e) {
            $this->error('Selective cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Handle interactive selection mode
     */
    private function handleInteractiveMode(): int
    {
        $this->info('Interactive Selection Mode');
        $this->line('Choose what you want to clean...');
        $this->newLine();

        // File type selection
        $availableTypes = ['php', 'js', 'css', 'blade.php'];
        $selectedTypes = $this->choice(
            'Which file types do you want to clean?',
            $availableTypes,
            null,
            null,
            true
        );
        $selectedTypes = is_array($selectedTypes) ? $selectedTypes : [$selectedTypes];

        // Directory selection
        $directories = $this->getAvailableDirectories();
        $selectedDirectories = [];
        
        if ($this->confirm('Do you want to limit to specific directories?', false)) {
            $selectedDirectories = $this->choice(
                'Which directories do you want to include?',
                $directories,
                null,
                null,
                true
            );
            $selectedDirectories = is_array($selectedDirectories) ? $selectedDirectories : [$selectedDirectories];
        }

        // Operation selection
        $availableOperations = [
            'imports' => 'Remove unused imports',
            'methods' => 'Remove unused methods',
            'variables' => 'Remove unused variables',
            'duplicates' => 'Refactor duplicate code',
            'components' => 'Create components from duplicates'
        ];

        $this->info('Select cleanup operations:');
        $selectedOperations = [];
        foreach ($availableOperations as $key => $description) {
            if ($this->confirm($description . '?', true)) {
                $selectedOperations[] = $key;
            }
        }

        // Execute with selections
        return $this->executeSelectiveCleanup($selectedTypes, $selectedDirectories, $selectedOperations);
    }

    /**
     * Handle direct mode with command options
     */
    private function handleDirectMode(): int
    {
        $types = $this->option('type') ?: ['php', 'js', 'css', 'blade.php'];
        $directories = $this->option('directory') ?: [];
        $operations = $this->option('operation') ?: ['imports', 'methods', 'variables', 'duplicates', 'components'];

        return $this->executeSelectiveCleanup($types, $directories, $operations);
    }

    /**
     * Execute selective cleanup
     */
    private function executeSelectiveCleanup(array $types, array $directories, array $operations): int
    {
        // Create configuration
        $config = new CleanupConfig([
            'dryRun' => $this->option('dry-run'),
            'includeFileTypes' => $types,
            'includePaths' => $directories,
            'removeUnusedImports' => in_array('imports', $operations),
            'removeUnusedMethods' => in_array('methods', $operations),
            'removeUnusedVariables' => in_array('variables', $operations),
            'refactorDuplicates' => in_array('duplicates', $operations),
            'createComponents' => in_array('components', $operations),
        ]);

        // Display selection summary
        $this->displaySelectionSummary($config, $types, $directories, $operations);

        // Confirm execution
        if (!$this->confirm('Proceed with selective cleanup analysis?', true)) {
            $this->info('Cleanup cancelled.');
            return Command::SUCCESS;
        }

        // Perform analysis with progress tracking
        $this->info('Analyzing selected scope...');
        $progressBar = $this->output->createProgressBar(4);
        $progressBar->setFormat('verbose');
        $progressBar->start();
        
        $progressBar->setMessage('Scanning files...');
        $analysis = $this->orchestrator->analyzeCodebase();
        $progressBar->advance();
        
        $progressBar->setMessage('Filtering results...');
        $filteredAnalysis = $this->filterAnalysis($analysis, $types, $directories);
        $progressBar->advance();
        
        $progressBar->setMessage('Generating cleanup plan...');
        $plan = $this->orchestrator->generateCleanupPlan($filteredAnalysis);
        $progressBar->advance();
        
        $progressBar->setMessage('Preparing results...');
        $progressBar->advance();
        $progressBar->finish();
        $this->newLine(2);
        
        // Display filtered results with enhanced preview
        $this->displayFilteredResults($filteredAnalysis, $operations);
        
        // Show detailed preview if requested
        if ($this->confirm('Would you like to see a detailed preview of changes?', false)) {
            $this->showSelectivePreview($plan, $operations);
        }

        // Execute cleanup with step-by-step confirmation
        if ($this->confirm('Execute cleanup on selected items?', true)) {
            if (!$config->dryRun && !$this->confirm('This will modify your files. Are you sure?', false)) {
                $this->info('Cleanup cancelled.');
                return Command::SUCCESS;
            }
            
            $report = $this->orchestrator->executeCleanup($config);
            $this->displayCleanupResults($report);
        }

        return Command::SUCCESS;
    }

    /**
     * Get available directories for selection
     */
    private function getAvailableDirectories(): array
    {
        $directories = [
            'app/',
            'app/Models/',
            'app/Controllers/',
            'app/Services/',
            'resources/views/',
            'resources/js/',
            'resources/css/',
            'public/js/',
            'public/css/',
        ];

        // Filter to only existing directories
        return array_filter($directories, function($dir) {
            return is_dir(base_path($dir));
        });
    }

    /**
     * Display selection summary
     */
    private function displaySelectionSummary(CleanupConfig $config, array $types, array $directories, array $operations): void
    {
        $this->info('Selection Summary:');
        
        $operationLabels = [
            'imports' => 'Remove unused imports',
            'methods' => 'Remove unused methods', 
            'variables' => 'Remove unused variables',
            'duplicates' => 'Refactor duplicates',
            'components' => 'Create components'
        ];
        
        $selectedOperationLabels = array_map(function($op) use ($operationLabels) {
            return $operationLabels[$op] ?? $op;
        }, $operations);

        $this->table(
            ['Scope', 'Selection'],
            [
                ['File Types', implode(', ', $types)],
                ['Directories', empty($directories) ? 'All directories' : implode(', ', $directories)],
                ['Operations', implode(', ', $selectedOperationLabels)],
                ['Mode', $config->dryRun ? 'Preview (Dry Run)' : 'Execute'],
            ]
        );
        $this->newLine();
    }

    /**
     * Filter analysis results based on selection
     */
    private function filterAnalysis(CodebaseAnalysis $analysis, array $types, array $directories): CodebaseAnalysis
    {
        $filtered = new CodebaseAnalysis();
        
        // Filter PHP files
        if (in_array('php', $types)) {
            $filtered->phpFiles = $this->filterFilesByDirectory($analysis->phpFiles, $directories);
        }
        
        // Filter JS files
        if (in_array('js', $types)) {
            $filtered->jsFiles = $this->filterFilesByDirectory($analysis->jsFiles, $directories);
        }
        
        // Filter CSS files
        if (in_array('css', $types)) {
            $filtered->cssFiles = $this->filterFilesByDirectory($analysis->cssFiles, $directories);
        }
        
        // Filter Blade files
        if (in_array('blade.php', $types) || in_array('blade', $types)) {
            $filtered->bladeFiles = $this->filterFilesByDirectory($analysis->bladeFiles, $directories);
        }
        
        return $filtered;
    }

    /**
     * Filter files by directory
     */
    private function filterFilesByDirectory(array $files, array $directories): array
    {
        if (empty($directories)) {
            return $files;
        }
        
        return array_filter($files, function($file) use ($directories) {
            $filePath = $file->filePath ?? '';
            foreach ($directories as $directory) {
                if (str_starts_with($filePath, $directory)) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Display filtered analysis results
     */
    private function displayFilteredResults(CodebaseAnalysis $analysis, array $operations): void
    {
        $this->info('Analysis Results for Selected Scope:');
        
        $results = [];
        
        // Count issues by operation type
        if (in_array('imports', $operations)) {
            $importCount = $this->countUnusedImports($analysis);
            $results[] = ['Unused Imports', $importCount, $importCount > 0 ? '⚠️' : '✅'];
        }
        
        if (in_array('methods', $operations)) {
            $methodCount = $this->countUnusedMethods($analysis);
            $results[] = ['Unused Methods', $methodCount, $methodCount > 0 ? '⚠️' : '✅'];
        }
        
        if (in_array('variables', $operations)) {
            $variableCount = $this->countUnusedVariables($analysis);
            $results[] = ['Unused Variables', $variableCount, $variableCount > 0 ? '⚠️' : '✅'];
        }
        
        if (in_array('duplicates', $operations)) {
            $duplicateCount = $this->countDuplicates($analysis);
            $results[] = ['Duplicate Code', $duplicateCount, $duplicateCount > 0 ? '⚠️' : '✅'];
        }
        
        if (in_array('components', $operations)) {
            $componentCount = $this->countComponentOpportunities($analysis);
            $results[] = ['Component Opportunities', $componentCount, $componentCount > 0 ? '💡' : '✅'];
        }
        
        if (!empty($results)) {
            $this->table(['Issue Type', 'Count', 'Status'], $results);
        } else {
            $this->info('No issues found in selected scope.');
        }
        
        $this->newLine();
    }

    /**
     * Display cleanup results
     */
    private function displayCleanupResults($report): void
    {
        $this->info('Selective Cleanup Results:');
        
        $this->table(
            ['Operation', 'Items Processed', 'Status'],
            [
                ['Imports Removed', $report->importsRemoved, $report->importsRemoved > 0 ? '✅' : '➖'],
                ['Methods Removed', $report->methodsRemoved, $report->methodsRemoved > 0 ? '✅' : '➖'],
                ['Duplicates Refactored', $report->duplicatesRefactored, $report->duplicatesRefactored > 0 ? '✅' : '➖'],
                ['Components Created', $report->componentsCreated, $report->componentsCreated > 0 ? '✅' : '➖'],
            ]
        );
        
        if ($report->sizeReductionMB > 0) {
            $this->info("💾 Size reduction: {$report->sizeReductionMB} MB");
        }
        
        $this->newLine();
        $this->info('✅ Selective cleanup completed!');
    }

    /**
     * Count unused imports
     */
    private function countUnusedImports(CodebaseAnalysis $analysis): int
    {
        $count = 0;
        foreach ($analysis->phpFiles as $file) {
            $count += count($file->unusedImports ?? []);
        }
        foreach ($analysis->jsFiles as $file) {
            $count += count($file->unusedImports ?? []);
        }
        return $count;
    }

    /**
     * Count unused methods
     */
    private function countUnusedMethods(CodebaseAnalysis $analysis): int
    {
        $count = 0;
        foreach ($analysis->phpFiles as $file) {
            $count += count($file->unusedMethods ?? []);
        }
        return $count;
    }

    /**
     * Count unused variables
     */
    private function countUnusedVariables(CodebaseAnalysis $analysis): int
    {
        $count = 0;
        foreach ($analysis->phpFiles as $file) {
            $count += count($file->unusedVariables ?? []);
        }
        foreach ($analysis->jsFiles as $file) {
            $count += count($file->unusedVariables ?? []);
        }
        return $count;
    }

    /**
     * Count duplicates
     */
    private function countDuplicates(CodebaseAnalysis $analysis): int
    {
        $count = 0;
        foreach ($analysis->phpFiles as $file) {
            $count += count($file->duplicateMethods ?? []);
        }
        foreach ($analysis->bladeFiles as $file) {
            $count += count($file->duplicateStructures ?? []);
        }
        return $count;
    }

    /**
     * Count component opportunities
     */
    private function countComponentOpportunities(CodebaseAnalysis $analysis): int
    {
        $count = 0;
        foreach ($analysis->bladeFiles as $file) {
            $count += count($file->componentOpportunities ?? []);
        }
        return $count;
    }

    /**
     * Show detailed preview for selective cleanup
     */
    private function showSelectivePreview($plan, array $operations): void
    {
        $this->newLine();
        $this->info('🔍 Selective Cleanup Preview');
        $this->line('Here are the specific changes that will be made:');
        $this->newLine();

        // Show operations that will be performed
        if (in_array('imports', $operations) && !empty($plan->importsToRemove)) {
            $this->info('📦 Import Removals:');
            foreach (array_slice($plan->importsToRemove, 0, 10) as $import) {
                $file = $import['file'] ?? 'Unknown';
                $importName = $import['import'] ?? $import;
                $this->line("  - Remove '{$importName}' from {$file}");
            }
            if (count($plan->importsToRemove) > 10) {
                $this->line('  ... and ' . (count($plan->importsToRemove) - 10) . ' more imports');
            }
            $this->newLine();
        }

        if (in_array('methods', $operations) && !empty($plan->methodsToRemove)) {
            $this->info('🔧 Method Removals:');
            foreach (array_slice($plan->methodsToRemove, 0, 10) as $method) {
                $class = $method['class'] ?? 'Unknown';
                $methodName = $method['method'] ?? $method;
                $this->line("  - Remove method '{$methodName}' from {$class}");
            }
            if (count($plan->methodsToRemove) > 10) {
                $this->line('  ... and ' . (count($plan->methodsToRemove) - 10) . ' more methods');
            }
            $this->newLine();
        }

        if (in_array('variables', $operations) && !empty($plan->variablesToRemove)) {
            $this->info('🔤 Variable Removals:');
            foreach (array_slice($plan->variablesToRemove, 0, 10) as $variable) {
                $file = $variable['file'] ?? 'Unknown';
                $variableName = $variable['variable'] ?? $variable;
                $this->line("  - Remove variable '{$variableName}' from {$file}");
            }
            if (count($plan->variablesToRemove) > 10) {
                $this->line('  ... and ' . (count($plan->variablesToRemove) - 10) . ' more variables');
            }
            $this->newLine();
        }

        if (in_array('duplicates', $operations) && !empty($plan->duplicatesToRefactor)) {
            $this->info('🔄 Duplicate Code Refactoring:');
            foreach (array_slice($plan->duplicatesToRefactor, 0, 5) as $duplicate) {
                $description = $duplicate['description'] ?? 'Duplicate code block';
                $files = implode(', ', $duplicate['files'] ?? []);
                $this->line("  - Refactor: {$description}");
                $this->line("    Files: {$files}");
            }
            if (count($plan->duplicatesToRefactor) > 5) {
                $this->line('  ... and ' . (count($plan->duplicatesToRefactor) - 5) . ' more duplicates');
            }
            $this->newLine();
        }

        if (in_array('components', $operations) && !empty($plan->componentsToCreate)) {
            $this->info('🧩 Component Creation:');
            foreach ($plan->componentsToCreate as $component) {
                $name = $component['name'] ?? 'New Component';
                $sources = implode(', ', $component['sources'] ?? []);
                $this->line("  - Create component: {$name}");
                $this->line("    Extracted from: {$sources}");
            }
            $this->newLine();
        }

        // Show impact summary
        $totalOperations = 0;
        if (in_array('imports', $operations)) $totalOperations += count($plan->importsToRemove);
        if (in_array('methods', $operations)) $totalOperations += count($plan->methodsToRemove);
        if (in_array('variables', $operations)) $totalOperations += count($plan->variablesToRemove);
        if (in_array('duplicates', $operations)) $totalOperations += count($plan->duplicatesToRefactor);
        if (in_array('components', $operations)) $totalOperations += count($plan->componentsToCreate);

        $this->info("📊 Total operations: {$totalOperations}");
        if ($plan->estimatedSizeReduction > 0) {
            $this->info("💾 Estimated size reduction: {$plan->estimatedSizeReduction} MB");
        }
        $this->newLine();
    }
}