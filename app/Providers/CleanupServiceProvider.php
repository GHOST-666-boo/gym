<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Cleanup\CleanupOrchestrator;
use App\Services\Cleanup\SafetyValidator;
use App\Services\Cleanup\ReportGenerator;
use App\Services\Cleanup\Contracts\PhpAnalyzerInterface;
use App\Services\Cleanup\Contracts\JavaScriptAnalyzerInterface;
use App\Services\Cleanup\Contracts\BladeAnalyzerInterface;
use App\Services\Cleanup\Contracts\CssAnalyzerInterface;
use App\Services\Cleanup\Contracts\LaravelAnalyzerInterface;
use App\Services\Cleanup\Contracts\OrphanedFileDetectorInterface;
use App\Services\Cleanup\Contracts\FileModificationServiceInterface;
use App\Services\Cleanup\Contracts\CodeRefactoringServiceInterface;
use App\Services\Cleanup\PhpAnalyzer;
use App\Services\Cleanup\JavaScriptAnalyzer;
use App\Services\Cleanup\BladeAnalyzer;
use App\Services\Cleanup\CssAnalyzer;
use App\Services\Cleanup\LaravelAnalyzer;
use App\Services\Cleanup\OrphanedFileDetector;
use App\Services\Cleanup\FileModificationService;
use App\Services\Cleanup\CodeRefactoringService;
use App\Console\Commands\CleanupAnalyzeCommand;
use App\Console\Commands\CleanupExecuteCommand;
use App\Console\Commands\CleanupReportCommand;
use App\Console\Commands\CleanupInteractiveCommand;
use App\Console\Commands\CleanupSelectiveCommand;

class CleanupServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(PhpAnalyzerInterface::class, PhpAnalyzer::class);
        $this->app->bind(JavaScriptAnalyzerInterface::class, JavaScriptAnalyzer::class);
        $this->app->bind(BladeAnalyzerInterface::class, BladeAnalyzer::class);
        $this->app->bind(CssAnalyzerInterface::class, CssAnalyzer::class);
        $this->app->bind(LaravelAnalyzerInterface::class, LaravelAnalyzer::class);
        $this->app->bind(OrphanedFileDetectorInterface::class, OrphanedFileDetector::class);
        $this->app->bind(FileModificationServiceInterface::class, FileModificationService::class);
        $this->app->bind(CodeRefactoringServiceInterface::class, CodeRefactoringService::class);
        
        // Register additional services
        $this->app->singleton(SafetyValidator::class);
        $this->app->singleton(ReportGenerator::class);
        $this->app->singleton(\App\Services\Cleanup\MetricsCollector::class);
        $this->app->singleton(\App\Services\Cleanup\OperationLogger::class);
        
        $this->app->singleton(CleanupOrchestrator::class, function ($app) {
            return new CleanupOrchestrator(
                $app->make(PhpAnalyzerInterface::class),
                $app->make(JavaScriptAnalyzerInterface::class),
                $app->make(BladeAnalyzerInterface::class),
                $app->make(CssAnalyzerInterface::class),
                $app->make(LaravelAnalyzerInterface::class),
                $app->make(OrphanedFileDetectorInterface::class),
                $app->make(SafetyValidator::class),
                $app->make(ReportGenerator::class),
                $app->make(FileModificationServiceInterface::class),
                $app->make(CodeRefactoringServiceInterface::class),
                $app->make(\App\Services\Cleanup\MetricsCollector::class),
                $app->make(\App\Services\Cleanup\OperationLogger::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupAnalyzeCommand::class,
                CleanupExecuteCommand::class,
                CleanupReportCommand::class,
                CleanupInteractiveCommand::class,
                CleanupSelectiveCommand::class,
            ]);
        }

        // Publish configuration file
        $this->publishes([
            __DIR__.'/../../config/cleanup.php' => config_path('cleanup.php'),
        ], 'cleanup-config');
    }
}