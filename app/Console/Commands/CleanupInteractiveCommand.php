<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\CodebaseAnalysis;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\CleanupReport;

class CleanupInteractiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cleanup:interactive 
                            {--preview : Show preview mode without making changes}
                            {--config= : Load configuration from file}';

    /**
     * The console command description.
     */
    protected $description = 'Interactive step-by-step cleanup process with user confirmation';

    private CleanupOrchestrator $orchestrator;
    private CleanupConfig $config;
    private CodebaseAnalysis $analysis;
    private CleanupPlan $plan;

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
        $this->info('🚀 Welcome to Interactive Cleanup Workflow');
        $this->newLine();

        try {
            // Step 1: Configuration
            $this->config = $this->configureCleanup();
            
            // Step 2: Analysis
            $this->analysis = $this->performAnalysis();
            
            // Step 3: Plan Generation
            $this->plan = $this->generateCleanupPlan();
            
            // Step 4: Review and Confirmation
            if (!$this->reviewAndConfirmPlan()) {
                $this->info('Cleanup cancelled by user.');
                return Command::SUCCESS;
            }
            
            // Step 5: Execution
            $report = $this->executeCleanup();
            
            // Step 6: Results
            $this->displayResults($report);
            
            $this->info('✅ Interactive cleanup completed successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Interactive cleanup failed: ' . $e->getMessage());
            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * Configure cleanup settings interactively
     */
    private function configureCleanup(): CleanupConfig
    {
        $this->info('📋 Step 1: Configuration');
        $this->line('Let\'s configure your cleanup preferences...');
        $this->newLine();

        // Load config from file if provided
        if ($this->option('config')) {
            $config = $this->loadConfigFromFile($this->option('config'));
            $this->info('Configuration loaded from file: ' . $this->option('config'));
        } else {
            $config = new CleanupConfig();
        }

        // Interactive configuration
        $config->dryRun = $this->option('preview') || 
                          $this->confirm('Run in preview mode (no changes will be made)?', true);

        if (!$config->dryRun) {
            $config->createBackup = $this->confirm('Create backup before cleanup?', true);
            $config->runTests = $this->confirm('Run tests after cleanup to validate changes?', true);
        }

        // File type selection
        $availableTypes = ['php', 'js', 'css', 'blade.php'];
        $selectedTypes = $this->choice(
            'Which file types should be included? (comma-separated)',
            $availableTypes,
            implode(',', $config->includeFileTypes),
            null,
            true
        );
        $config->includeFileTypes = is_array($selectedTypes) ? $selectedTypes : [$selectedTypes];

        // Path configuration
        if ($this->confirm('Do you want to specify paths to analyze?', false)) {
            $paths = $this->ask('Enter paths to analyze (comma-separated)', '');
            $config->includePaths = array_filter(array_map('trim', explode(',', $paths)));
        }

        if ($this->confirm('Do you want to exclude any paths?', false)) {
            $excludePaths = $this->ask('Enter paths to exclude (comma-separated)', 'vendor,node_modules');
            $config->excludePaths = array_merge(
                $config->excludePaths,
                array_filter(array_map('trim', explode(',', $excludePaths)))
            );
        }

        // Operation selection
        $this->info('Select cleanup operations to perform:');
        $config->removeUnusedImports = $this->confirm('Remove unused imports?', true);
        $config->removeUnusedMethods = $this->confirm('Remove unused methods?', true);
        $config->removeUnusedVariables = $this->confirm('Remove unused variables?', true);
        $config->refactorDuplicates = $this->confirm('Refactor duplicate code?', true);
        $config->createComponents = $this->confirm('Create components from duplicates?', true);

        // Advanced settings
        if ($this->confirm('Configure advanced settings?', false)) {
            $config->batchSize = (int) $this->ask('Batch size for processing files', $config->batchSize);
            $maxFileSizeMB = $this->ask('Maximum file size to process (MB)', $config->maxFileSize / 1048576);
            $config->maxFileSize = (int) ($maxFileSizeMB * 1048576);
        }

        $this->displayConfigurationSummary($config);
        
        if (!$this->confirm('Proceed with this configuration?', true)) {
            return $this->configureCleanup(); // Restart configuration
        }

        return $config;
    }

    /**
     * Perform codebase analysis
     */
    private function performAnalysis(): CodebaseAnalysis
    {
        $this->newLine();
        $this->info('🔍 Step 2: Codebase Analysis');
        $this->line('Analyzing your codebase for cleanup opportunities...');
        
        $progressBar = $this->output->createProgressBar(5);
        $progressBar->setFormat('verbose');
        $progressBar->start();

        try {
            $progressBar->setMessage('Scanning PHP files...');
            $progressBar->advance();
            
            $progressBar->setMessage('Scanning JavaScript files...');
            $progressBar->advance();
            
            $progressBar->setMessage('Scanning CSS files...');
            $progressBar->advance();
            
            $progressBar->setMessage('Scanning Blade templates...');
            $progressBar->advance();
            
            $progressBar->setMessage('Building dependency graph...');
            $progressBar->advance();
            
            $analysis = $this->orchestrator->analyzeCodebase();
            
            $progressBar->finish();
            $this->newLine(2);
            
            $this->displayAnalysisSummary($analysis);
            
            return $analysis;
            
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            throw $e;
        }
    }

    /**
     * Generate cleanup plan
     */
    private function generateCleanupPlan(): CleanupPlan
    {
        $this->newLine();
        $this->info('📝 Step 3: Cleanup Plan Generation');
        $this->line('Generating cleanup plan based on analysis...');
        
        $plan = $this->orchestrator->generateCleanupPlan($this->analysis);
        
        $this->displayPlanSummary($plan);
        
        return $plan;
    }

    /**
     * Review and confirm cleanup plan
     */
    private function reviewAndConfirmPlan(): bool
    {
        $this->newLine();
        $this->info('👀 Step 4: Plan Review & Confirmation');
        
        // Show detailed plan sections with step-by-step confirmation
        if (!$this->reviewPlanStepByStep()) {
            return false;
        }
        
        // Risk assessment
        $this->showRiskAssessment();
        
        // Final confirmation with enhanced preview
        if ($this->config->dryRun) {
            $this->showEnhancedPreview();
            return $this->confirm('Proceed with preview analysis?', true);
        } else {
            $this->warn('⚠️  This will modify your codebase!');
            $this->warn('⚠️  Make sure you have committed your current changes!');
            
            if (!$this->confirm('Do you want to see a detailed preview first?', true)) {
                return false;
            }
            
            $this->showEnhancedPreview();
            
            return $this->confirm('Are you absolutely sure you want to proceed with these changes?', false);
        }
    }

    /**
     * Execute cleanup operations
     */
    private function executeCleanup(): CleanupReport
    {
        $this->newLine();
        $this->info('⚙️  Step 5: Cleanup Execution');
        
        if ($this->config->dryRun) {
            $this->line('Running in preview mode - no changes will be made...');
        } else {
            $this->line('Executing cleanup operations...');
        }
        
        $progressBar = $this->output->createProgressBar($this->plan->getTotalOperations());
        $progressBar->setFormat('verbose');
        $progressBar->start();
        
        try {
            // Simulate progress updates during cleanup
            $progressBar->setMessage('Removing unused imports...');
            $progressBar->advance(count($this->plan->importsToRemove));
            
            $progressBar->setMessage('Removing unused methods...');
            $progressBar->advance(count($this->plan->methodsToRemove));
            
            $progressBar->setMessage('Refactoring duplicates...');
            $progressBar->advance(count($this->plan->duplicatesToRefactor));
            
            $progressBar->setMessage('Creating components...');
            $progressBar->advance(count($this->plan->componentsToCreate));
            
            $report = $this->orchestrator->executeCleanup($this->config);
            
            $progressBar->finish();
            $this->newLine(2);
            
            return $report;
            
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            throw $e;
        }
    }

    /**
     * Display cleanup results
     */
    private function displayResults(CleanupReport $report): void
    {
        $this->newLine();
        $this->info('📊 Step 6: Results Summary');
        
        if ($this->config->dryRun) {
            $this->info('Preview Results (no changes were made):');
        } else {
            $this->info('Cleanup Results:');
        }
        
        $this->table(
            ['Operation', 'Count', 'Impact'],
            [
                ['Files Removed', $report->filesRemoved, $this->getImpactEmoji($report->filesRemoved)],
                ['Lines Removed', $report->linesRemoved, $this->getImpactEmoji($report->linesRemoved, 100)],
                ['Imports Cleaned', $report->importsRemoved, $this->getImpactEmoji($report->importsRemoved, 10)],
                ['Methods Removed', $report->methodsRemoved, $this->getImpactEmoji($report->methodsRemoved)],
                ['Duplicates Fixed', $report->duplicatesRefactored, $this->getImpactEmoji($report->duplicatesRefactored)],
                ['Components Created', $report->componentsCreated, $this->getImpactEmoji($report->componentsCreated)],
            ]
        );

        if ($report->sizeReductionMB > 0) {
            $this->info("💾 Total size reduction: {$report->sizeReductionMB} MB");
        }

        // Show next steps
        $this->showNextSteps($report);
    }

    /**
     * Display configuration summary
     */
    private function displayConfigurationSummary(CleanupConfig $config): void
    {
        $this->newLine();
        $this->info('Configuration Summary:');
        
        $operations = [];
        if ($config->removeUnusedImports) $operations[] = 'Unused imports';
        if ($config->removeUnusedMethods) $operations[] = 'Unused methods';
        if ($config->removeUnusedVariables) $operations[] = 'Unused variables';
        if ($config->refactorDuplicates) $operations[] = 'Duplicate code';
        if ($config->createComponents) $operations[] = 'Component creation';
        
        $this->table(
            ['Setting', 'Value'],
            [
                ['Mode', $config->dryRun ? '🔍 Preview' : '⚡ Execute'],
                ['File Types', implode(', ', $config->includeFileTypes)],
                ['Operations', implode(', ', $operations)],
                ['Create Backup', $config->createBackup ? '✅ Yes' : '❌ No'],
                ['Run Tests', $config->runTests ? '✅ Yes' : '❌ No'],
                ['Batch Size', $config->batchSize],
            ]
        );
    }

    /**
     * Display analysis summary
     */
    private function displayAnalysisSummary(CodebaseAnalysis $analysis): void
    {
        $this->info('Analysis Complete! Here\'s what we found:');
        
        $this->table(
            ['File Type', 'Count', 'Issues Found'],
            [
                ['PHP Files', count($analysis->phpFiles), $this->countPhpIssues($analysis)],
                ['JavaScript Files', count($analysis->jsFiles), $this->countJsIssues($analysis)],
                ['CSS Files', count($analysis->cssFiles), $this->countCssIssues($analysis)],
                ['Blade Templates', count($analysis->bladeFiles), $this->countBladeIssues($analysis)],
                ['Orphaned Files', count($analysis->orphanedFiles ?? []), '🗑️'],
            ]
        );
    }

    /**
     * Display plan summary
     */
    private function displayPlanSummary(CleanupPlan $plan): void
    {
        $this->info('Cleanup Plan Generated:');
        
        $this->table(
            ['Operation', 'Items', 'Estimated Impact'],
            [
                ['Remove Files', count($plan->filesToDelete), $this->getImpactLevel(count($plan->filesToDelete), 'files')],
                ['Remove Imports', count($plan->importsToRemove), $this->getImpactLevel(count($plan->importsToRemove), 'imports')],
                ['Remove Methods', count($plan->methodsToRemove), $this->getImpactLevel(count($plan->methodsToRemove), 'methods')],
                ['Refactor Duplicates', count($plan->duplicatesToRefactor), $this->getImpactLevel(count($plan->duplicatesToRefactor), 'duplicates')],
                ['Create Components', count($plan->componentsToCreate), $this->getImpactLevel(count($plan->componentsToCreate), 'components')],
            ]
        );
        
        if ($plan->estimatedSizeReduction > 0) {
            $this->info("💾 Estimated size reduction: {$plan->estimatedSizeReduction} MB");
        }
    }

    /**
     * Review plan step by step with user confirmation
     */
    private function reviewPlanStepByStep(): bool
    {
        $this->info('Let\'s review each cleanup operation step by step...');
        $this->newLine();
        
        // Step 1: File deletions
        if (!empty($this->plan->filesToDelete)) {
            if (!$this->reviewFileDeletions()) {
                return false;
            }
        }
        
        // Step 2: Import removals
        if (!empty($this->plan->importsToRemove)) {
            if (!$this->reviewImportRemovals()) {
                return false;
            }
        }
        
        // Step 3: Method removals
        if (!empty($this->plan->methodsToRemove)) {
            if (!$this->reviewMethodRemovals()) {
                return false;
            }
        }
        
        // Step 4: Variable removals
        if (!empty($this->plan->variablesToRemove)) {
            if (!$this->reviewVariableRemovals()) {
                return false;
            }
        }
        
        // Step 5: Duplicate refactoring
        if (!empty($this->plan->duplicatesToRefactor)) {
            if (!$this->reviewDuplicateRefactoring()) {
                return false;
            }
        }
        
        // Step 6: Component creation
        if (!empty($this->plan->componentsToCreate)) {
            if (!$this->reviewComponentCreation()) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Review file deletions with user confirmation
     */
    private function reviewFileDeletions(): bool
    {
        $this->warn('📁 File Deletions Review');
        $this->line('The following files will be deleted:');
        $this->newLine();
        
        foreach ($this->plan->filesToDelete as $index => $file) {
            $this->line(sprintf('  %d. 🗑️  %s', $index + 1, $file));
            
            if (($index + 1) % 5 === 0 && $index < count($this->plan->filesToDelete) - 1) {
                if (!$this->confirm('Continue showing more files?', true)) {
                    $remaining = count($this->plan->filesToDelete) - $index - 1;
                    $this->line("  ... and {$remaining} more files");
                    break;
                }
            }
        }
        
        $this->newLine();
        return $this->confirm('Approve file deletions?', true);
    }

    /**
     * Review import removals with user confirmation
     */
    private function reviewImportRemovals(): bool
    {
        $this->info('📦 Import Removals Review');
        $this->line('The following unused imports will be removed:');
        $this->newLine();
        
        $groupedImports = $this->groupImportsByFile($this->plan->importsToRemove);
        
        foreach ($groupedImports as $file => $imports) {
            $this->line("  📄 {$file}:");
            foreach ($imports as $import) {
                $this->line("    - {$import}");
            }
            $this->newLine();
            
            if (!$this->confirm("Approve import removals for {$file}?", true)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Review method removals with user confirmation
     */
    private function reviewMethodRemovals(): bool
    {
        $this->info('🔧 Method Removals Review');
        $this->line('The following unused methods will be removed:');
        $this->newLine();
        
        $groupedMethods = $this->groupMethodsByClass($this->plan->methodsToRemove);
        
        foreach ($groupedMethods as $class => $methods) {
            $this->line("  🏗️  {$class}:");
            foreach ($methods as $method) {
                $this->line("    - {$method}");
            }
            $this->newLine();
            
            if (!$this->confirm("Approve method removals for {$class}?", true)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Review variable removals with user confirmation
     */
    private function reviewVariableRemovals(): bool
    {
        $this->info('🔤 Variable Removals Review');
        $this->line('The following unused variables will be removed:');
        $this->newLine();
        
        $groupedVariables = $this->groupVariablesByFile($this->plan->variablesToRemove);
        
        foreach ($groupedVariables as $file => $variables) {
            $this->line("  📄 {$file}:");
            foreach ($variables as $variable) {
                $this->line("    - {$variable}");
            }
            $this->newLine();
            
            if (!$this->confirm("Approve variable removals for {$file}?", true)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Review duplicate refactoring with user confirmation
     */
    private function reviewDuplicateRefactoring(): bool
    {
        $this->info('🔄 Duplicate Code Refactoring Review');
        $this->line('The following duplicate code will be refactored:');
        $this->newLine();
        
        foreach ($this->plan->duplicatesToRefactor as $index => $duplicate) {
            $this->line(sprintf('  %d. 🔄 %s', $index + 1, $duplicate['description'] ?? 'Duplicate code block'));
            $this->line("     Files: " . implode(', ', $duplicate['files'] ?? []));
            $this->line("     Suggested refactoring: " . ($duplicate['suggestion'] ?? 'Extract to common method'));
            $this->newLine();
            
            if (!$this->confirm('Approve this refactoring?', true)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Review component creation with user confirmation
     */
    private function reviewComponentCreation(): bool
    {
        $this->info('🧩 Component Creation Review');
        $this->line('The following components will be created:');
        $this->newLine();
        
        foreach ($this->plan->componentsToCreate as $index => $component) {
            $this->line(sprintf('  %d. 🧩 %s', $index + 1, $component['name'] ?? 'New Component'));
            $this->line("     Location: " . ($component['path'] ?? 'resources/views/components/'));
            $this->line("     Extracted from: " . implode(', ', $component['sources'] ?? []));
            $this->newLine();
            
            if (!$this->confirm('Approve this component creation?', true)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Show enhanced preview with detailed change information
     */
    private function showEnhancedPreview(): void
    {
        $this->newLine();
        $this->info('🔍 Enhanced Preview Mode');
        $this->line('Here\'s exactly what will happen:');
        $this->newLine();
        
        // Show operation summary
        $operations = [];
        if (!empty($this->plan->filesToDelete)) {
            $operations[] = sprintf('Delete %d files', count($this->plan->filesToDelete));
        }
        if (!empty($this->plan->importsToRemove)) {
            $operations[] = sprintf('Remove %d unused imports', count($this->plan->importsToRemove));
        }
        if (!empty($this->plan->methodsToRemove)) {
            $operations[] = sprintf('Remove %d unused methods', count($this->plan->methodsToRemove));
        }
        if (!empty($this->plan->variablesToRemove)) {
            $operations[] = sprintf('Remove %d unused variables', count($this->plan->variablesToRemove));
        }
        if (!empty($this->plan->duplicatesToRefactor)) {
            $operations[] = sprintf('Refactor %d duplicate code blocks', count($this->plan->duplicatesToRefactor));
        }
        if (!empty($this->plan->componentsToCreate)) {
            $operations[] = sprintf('Create %d new components', count($this->plan->componentsToCreate));
        }
        
        if (!empty($operations)) {
            $this->info('Operations to be performed:');
            foreach ($operations as $operation) {
                $this->line("  ✓ {$operation}");
            }
        } else {
            $this->info('No operations will be performed - codebase is already clean!');
        }
        
        $this->newLine();
        
        // Show estimated impact
        if ($this->plan->estimatedSizeReduction > 0) {
            $this->info("💾 Estimated size reduction: {$this->plan->estimatedSizeReduction} MB");
        }
        
        // Show safety measures
        $this->info('🛡️  Safety measures in place:');
        if ($this->config->createBackup) {
            $this->line('  ✓ Git backup will be created before changes');
        }
        if ($this->config->runTests) {
            $this->line('  ✓ Tests will be run after cleanup to validate changes');
        }
        $this->line('  ✓ All operations are reversible');
        $this->line('  ✓ Changes will be applied incrementally with validation');
        
        $this->newLine();
    }

    /**
     * Show detailed plan sections
     */
    private function showDetailedPlan(): void
    {
        if ($this->confirm('Would you like to see detailed cleanup items?', false)) {
            
            // Show files to be deleted
            if (!empty($this->plan->filesToDelete)) {
                $this->warn('Files to be deleted:');
                foreach (array_slice($this->plan->filesToDelete, 0, 10) as $file) {
                    $this->line("  🗑️  {$file}");
                }
                if (count($this->plan->filesToDelete) > 10) {
                    $this->line('  ... and ' . (count($this->plan->filesToDelete) - 10) . ' more files');
                }
                $this->newLine();
            }
            
            // Show imports to be removed
            if (!empty($this->plan->importsToRemove)) {
                $this->info('Unused imports to be removed:');
                foreach (array_slice($this->plan->importsToRemove, 0, 5) as $import) {
                    $this->line("  📦 {$import}");
                }
                if (count($this->plan->importsToRemove) > 5) {
                    $this->line('  ... and ' . (count($this->plan->importsToRemove) - 5) . ' more imports');
                }
                $this->newLine();
            }
            
            // Show methods to be removed
            if (!empty($this->plan->methodsToRemove)) {
                $this->info('Unused methods to be removed:');
                foreach (array_slice($this->plan->methodsToRemove, 0, 5) as $method) {
                    $this->line("  🔧 {$method}");
                }
                if (count($this->plan->methodsToRemove) > 5) {
                    $this->line('  ... and ' . (count($this->plan->methodsToRemove) - 5) . ' more methods');
                }
                $this->newLine();
            }
        }
    }

    /**
     * Show risk assessment
     */
    private function showRiskAssessment(): void
    {
        $this->info('🛡️  Risk Assessment:');
        
        $risks = [];
        
        // Assess risks based on plan
        if (count($this->plan->filesToDelete) > 10) {
            $risks[] = ['High', 'Large number of files to be deleted', 'Review file list carefully'];
        }
        
        if (count($this->plan->methodsToRemove) > 20) {
            $risks[] = ['Medium', 'Many methods will be removed', 'Ensure comprehensive test coverage'];
        }
        
        if (!$this->config->createBackup) {
            $risks[] = ['High', 'No backup will be created', 'Consider enabling backup creation'];
        }
        
        if (!$this->config->runTests) {
            $risks[] = ['Medium', 'Tests will not be run after cleanup', 'Manual testing recommended'];
        }
        
        if (empty($risks)) {
            $risks[] = ['Low', 'No significant risks detected', 'Proceed with confidence'];
        }
        
        $this->table(['Risk Level', 'Description', 'Recommendation'], $risks);
        $this->newLine();
    }

    /**
     * Show next steps
     */
    private function showNextSteps(CleanupReport $report): void
    {
        $this->newLine();
        $this->info('🎯 Next Steps:');
        
        if ($this->config->dryRun) {
            $this->line('1. Review the proposed changes above');
            $this->line('2. Run without --preview to execute the cleanup');
            $this->line('3. Consider running tests after cleanup');
        } else {
            $this->line('1. Run your test suite to verify functionality');
            $this->line('2. Review the changes in your version control');
            $this->line('3. Commit the cleaned codebase');
            
            if (!empty($report->maintenanceRecommendations)) {
                $this->line('4. Consider the maintenance recommendations in the report');
            }
        }
        
        $this->newLine();
        $this->info('💡 Pro Tips:');
        $this->line('• Run cleanup:report to generate a detailed report');
        $this->line('• Set up regular cleanup schedules for ongoing maintenance');
        $this->line('• Use --config to save and reuse cleanup configurations');
    }

    /**
     * Load configuration from file
     */
    private function loadConfigFromFile(string $filePath): CleanupConfig
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Configuration file not found: {$filePath}");
        }
        
        $data = json_decode(file_get_contents($filePath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in configuration file: " . json_last_error_msg());
        }
        
        return new CleanupConfig($data);
    }

    /**
     * Count PHP issues
     */
    private function countPhpIssues(CodebaseAnalysis $analysis): string
    {
        $count = 0;
        foreach ($analysis->phpFiles as $file) {
            $count += count($file->unusedImports ?? []);
            $count += count($file->unusedMethods ?? []);
            $count += count($file->duplicateMethods ?? []);
        }
        return $count > 0 ? "⚠️  {$count}" : '✅ 0';
    }

    /**
     * Count JavaScript issues
     */
    private function countJsIssues(CodebaseAnalysis $analysis): string
    {
        $count = 0;
        foreach ($analysis->jsFiles as $file) {
            $count += count($file->unusedImports ?? []);
            $count += count($file->unusedVariables ?? []);
        }
        return $count > 0 ? "⚠️  {$count}" : '✅ 0';
    }

    /**
     * Count CSS issues
     */
    private function countCssIssues(CodebaseAnalysis $analysis): string
    {
        $count = 0;
        foreach ($analysis->cssFiles as $file) {
            $count += count($file->unusedRules ?? []);
            $count += count($file->duplicateRules ?? []);
        }
        return $count > 0 ? "⚠️  {$count}" : '✅ 0';
    }

    /**
     * Count Blade issues
     */
    private function countBladeIssues(CodebaseAnalysis $analysis): string
    {
        $count = 0;
        foreach ($analysis->bladeFiles as $file) {
            $count += count($file->unusedVariables ?? []);
            $count += count($file->duplicateStructures ?? []);
        }
        return $count > 0 ? "⚠️  {$count}" : '✅ 0';
    }

    /**
     * Get impact level for display
     */
    private function getImpactLevel(int $count, string $type): string
    {
        $thresholds = [
            'files' => ['low' => 5, 'medium' => 20],
            'imports' => ['low' => 10, 'medium' => 50],
            'methods' => ['low' => 5, 'medium' => 20],
            'duplicates' => ['low' => 3, 'medium' => 10],
            'components' => ['low' => 2, 'medium' => 5],
        ];

        $threshold = $thresholds[$type] ?? ['low' => 10, 'medium' => 50];

        if ($count === 0) {
            return '✅ None';
        } elseif ($count >= $threshold['medium']) {
            return '🔴 High';
        } elseif ($count >= $threshold['low']) {
            return '🟡 Medium';
        } else {
            return '🟢 Low';
        }
    }

    /**
     * Get impact emoji
     */
    private function getImpactEmoji(int $count, int $threshold = 5): string
    {
        if ($count === 0) {
            return '✅';
        } elseif ($count >= $threshold * 2) {
            return '🔴';
        } elseif ($count >= $threshold) {
            return '🟡';
        } else {
            return '🟢';
        }
    }

    /**
     * Group imports by file
     */
    private function groupImportsByFile(array $imports): array
    {
        $grouped = [];
        foreach ($imports as $import) {
            $file = $import['file'] ?? 'Unknown file';
            $importName = $import['import'] ?? $import;
            $grouped[$file][] = $importName;
        }
        return $grouped;
    }

    /**
     * Group methods by class
     */
    private function groupMethodsByClass(array $methods): array
    {
        $grouped = [];
        foreach ($methods as $method) {
            $class = $method['class'] ?? 'Unknown class';
            $methodName = $method['method'] ?? $method;
            $grouped[$class][] = $methodName;
        }
        return $grouped;
    }

    /**
     * Group variables by file
     */
    private function groupVariablesByFile(array $variables): array
    {
        $grouped = [];
        foreach ($variables as $variable) {
            $file = $variable['file'] ?? 'Unknown file';
            $variableName = $variable['variable'] ?? $variable;
            $grouped[$file][] = $variableName;
        }
        return $grouped;
    }
}