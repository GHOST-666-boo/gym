<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\Models\CleanupConfig;
use App\Services\Cleanup\Models\CleanupReport;

class CleanupExecuteCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cleanup:execute 
                            {--dry-run : Preview changes without executing them}
                            {--no-backup : Skip creating backup before cleanup}
                            {--no-tests : Skip running tests after cleanup}
                            {--path=* : Specific paths to clean (default: entire codebase)}
                            {--exclude=* : Paths to exclude from cleanup}
                            {--types=* : File types to include (php,js,css,blade.php)}
                            {--imports : Only remove unused imports}
                            {--methods : Only remove unused methods}
                            {--variables : Only remove unused variables}
                            {--duplicates : Only refactor duplicate code}
                            {--components : Only create components from duplicates}
                            {--batch-size=50 : Number of files to process in each batch}
                            {--force : Execute without confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Execute cleanup operations on the codebase';

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
        $this->info('Starting cleanup execution...');
        
        try {
            // Create configuration from command options
            $config = $this->createConfigFromOptions();
            
            // Display configuration and get confirmation
            if (!$this->confirmExecution($config)) {
                $this->info('Cleanup cancelled by user.');
                return Command::SUCCESS;
            }
            
            // Execute cleanup
            $this->info('Executing cleanup operations...');
            $report = $this->orchestrator->executeCleanup($config);
            
            // Display results
            $this->displayExecutionResults($report);
            
            $this->info('Cleanup completed successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Cleanup execution failed: ' . $e->getMessage());
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
            'dryRun' => $this->option('dry-run'),
            'createBackup' => !$this->option('no-backup'),
            'runTests' => !$this->option('no-tests'),
            'batchSize' => (int) $this->option('batch-size'),
        ]);

        // Set paths to clean
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

        // Set specific cleanup operations
        if ($this->hasSpecificOperations()) {
            $config->removeUnusedImports = $this->option('imports');
            $config->removeUnusedMethods = $this->option('methods');
            $config->removeUnusedVariables = $this->option('variables');
            $config->refactorDuplicates = $this->option('duplicates');
            $config->createComponents = $this->option('components');
        }

        return $config;
    }

    /**
     * Check if specific operations are requested
     */
    private function hasSpecificOperations(): bool
    {
        return $this->option('imports') || 
               $this->option('methods') || 
               $this->option('variables') || 
               $this->option('duplicates') || 
               $this->option('components');
    }

    /**
     * Confirm execution with user
     */
    private function confirmExecution(CleanupConfig $config): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $this->displayConfiguration($config);
        
        if ($config->dryRun) {
            $this->warn('DRY RUN MODE: No changes will be made to files.');
            return $this->confirm('Proceed with dry run analysis?', true);
        }

        $this->warn('This will modify your codebase!');
        
        if (!$config->createBackup) {
            $this->error('WARNING: Backup creation is disabled!');
        }
        
        if (!$config->runTests) {
            $this->warn('WARNING: Test validation is disabled!');
        }

        return $this->confirm('Are you sure you want to proceed with cleanup execution?', false);
    }

    /**
     * Display current configuration
     */
    private function displayConfiguration(CleanupConfig $config): void
    {
        $this->info('Cleanup Configuration:');
        
        $operations = [];
        if ($config->removeUnusedImports) $operations[] = 'Remove unused imports';
        if ($config->removeUnusedMethods) $operations[] = 'Remove unused methods';
        if ($config->removeUnusedVariables) $operations[] = 'Remove unused variables';
        if ($config->refactorDuplicates) $operations[] = 'Refactor duplicates';
        if ($config->createComponents) $operations[] = 'Create components';
        
        $this->table(
            ['Setting', 'Value'],
            [
                ['Mode', $config->dryRun ? 'Dry Run' : 'Execute'],
                ['Create Backup', $config->createBackup ? 'Yes' : 'No'],
                ['Run Tests', $config->runTests ? 'Yes' : 'No'],
                ['File Types', implode(', ', $config->includeFileTypes)],
                ['Excluded Paths', implode(', ', $config->excludePaths) ?: 'None'],
                ['Operations', implode(', ', $operations) ?: 'All'],
                ['Batch Size', $config->batchSize],
            ]
        );
        $this->newLine();
    }

    /**
     * Display execution results
     */
    private function displayExecutionResults(CleanupReport $report): void
    {
        if ($report->isDryRun ?? false) {
            $this->info('Dry Run Results - No changes were made:');
        } else {
            $this->info('Cleanup Execution Results:');
        }
        
        // Summary statistics
        $this->table(
            ['Operation', 'Count', 'Impact'],
            [
                ['Files Removed', $report->filesRemoved, $this->getImpactLevel($report->filesRemoved, 'files')],
                ['Lines Removed', $report->linesRemoved, $this->getImpactLevel($report->linesRemoved, 'lines')],
                ['Imports Removed', $report->importsRemoved, $this->getImpactLevel($report->importsRemoved, 'imports')],
                ['Methods Removed', $report->methodsRemoved, $this->getImpactLevel($report->methodsRemoved, 'methods')],
                ['Duplicates Refactored', $report->duplicatesRefactored, $this->getImpactLevel($report->duplicatesRefactored, 'duplicates')],
                ['Components Created', $report->componentsCreated, $this->getImpactLevel($report->componentsCreated, 'components')],
            ]
        );

        // Performance improvements
        if (!empty($report->performanceImprovements)) {
            $this->newLine();
            $this->info('Performance Improvements:');
            
            foreach ($report->performanceImprovements as $category => $improvement) {
                $this->line("  • {$improvement['description']}: {$improvement['impact_level']} impact");
            }
        }

        // Size reduction
        if ($report->sizeReductionMB > 0) {
            $this->newLine();
            $this->info("Total size reduction: {$report->sizeReductionMB} MB");
        }

        // Risk assessments
        if (!empty($report->riskAssessments)) {
            $this->newLine();
            $this->warn('Risk Assessments:');
            
            foreach ($report->riskAssessments as $risk) {
                if (is_array($risk)) {
                    $level = $risk['level'] ?? $risk['severity'] ?? 'Unknown';
                    $description = $risk['description'] ?? 'No description';
                } else {
                    // Handle RiskAssessment object
                    $level = $risk->severity ?? 'Unknown';
                    $description = $risk->description ?? $risk->title ?? 'No description';
                }
                
                $color = match(strtolower($level)) {
                    'high', 'critical' => 'error',
                    'medium' => 'warn',
                    default => 'info'
                };
                
                $this->$color("  • [" . ucfirst($level) . "] {$description}");
            }
        }

        // Maintenance recommendations
        if (!empty($report->maintenanceRecommendations)) {
            $this->newLine();
            $this->info('Maintenance Recommendations:');
            
            foreach (array_slice($report->maintenanceRecommendations, 0, 5) as $recommendation) {
                if (is_array($recommendation)) {
                    $text = $recommendation['description'] ?? $recommendation['title'] ?? 'No description';
                } else {
                    // Handle MaintenanceRecommendation object
                    $text = $recommendation->description ?? $recommendation->title ?? 'No description';
                }
                $this->line("  • {$text}");
            }
            
            if (count($report->maintenanceRecommendations) > 5) {
                $this->info('  ... and ' . (count($report->maintenanceRecommendations) - 5) . ' more recommendations');
            }
        }

        // Execution summary
        if (!empty($report->executionSummary)) {
            $this->newLine();
            $this->info('Execution Summary:');
            
            $summary = $report->executionSummary;
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Operations', $summary['total_operations'] ?? 0],
                    ['Execution Time', $summary['execution_time'] ?? 'N/A'],
                    ['Memory Usage', $summary['memory_usage'] ?? 'N/A'],
                    ['Success Rate', ($summary['success_rate'] ?? 0) . '%'],
                ]
            );
        }

        // Next steps
        $this->newLine();
        if ($report->isDryRun ?? false) {
            $this->info('Next Steps:');
            $this->line('  • Review the proposed changes above');
            $this->line('  • Run without --dry-run to execute the cleanup');
            $this->line('  • Use cleanup:report to generate a detailed report');
        } else {
            $this->info('Cleanup completed! Consider running:');
            $this->line('  • cleanup:report to generate a detailed report');
            $this->line('  • Your test suite to verify functionality');
            $this->line('  • Git commit to save the cleaned codebase');
        }
    }

    /**
     * Get impact level for a count
     */
    private function getImpactLevel(int $count, string $type): string
    {
        if ($count === 0) {
            return 'None';
        }

        $thresholds = [
            'files' => ['low' => 5, 'medium' => 20],
            'lines' => ['low' => 100, 'medium' => 500],
            'imports' => ['low' => 10, 'medium' => 50],
            'methods' => ['low' => 5, 'medium' => 20],
            'duplicates' => ['low' => 3, 'medium' => 10],
            'components' => ['low' => 2, 'medium' => 5],
        ];

        $threshold = $thresholds[$type] ?? ['low' => 10, 'medium' => 50];

        if ($count >= $threshold['medium']) {
            return 'High';
        } elseif ($count >= $threshold['low']) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }
}