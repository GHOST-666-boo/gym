<?php

namespace App\Console\Commands;

use App\Services\Cleanup\CrossFileDuplicateDetector;
use App\Services\Cleanup\PhpAnalyzer;
use App\Services\Cleanup\JavaScriptAnalyzer;
use App\Services\Cleanup\CssAnalyzer;
use App\Services\Cleanup\BladeAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AnalyzeDuplicatesCommand extends Command
{
    protected $signature = 'cleanup:analyze-duplicates {--type=all : Type of duplicates to analyze (php|js|css|blade|all)}';
    protected $description = 'Analyze codebase for duplicate code patterns';

    public function handle(): int
    {
        $type = $this->option('type');
        
        $this->info('🔍 Analyzing codebase for duplicate patterns...');
        
        try {
            switch ($type) {
                case 'php':
                    $this->analyzePhpDuplicates();
                    break;
                case 'js':
                    $this->analyzeJavaScriptDuplicates();
                    break;
                case 'css':
                    $this->analyzeCssDuplicates();
                    break;
                case 'blade':
                    $this->analyzeBladeDuplicates();
                    break;
                case 'all':
                default:
                    $this->analyzeAllDuplicates();
                    break;
            }
            
            $this->info('✅ Duplicate analysis completed successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Error during duplicate analysis: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function analyzePhpDuplicates(): void
    {
        $this->info('Analyzing PHP files for duplicate methods...');
        
        $phpAnalyzer = new PhpAnalyzer();
        $phpFiles = $this->getPhpFiles();
        $analyses = [];
        
        foreach ($phpFiles as $file) {
            $analysis = $phpAnalyzer->parseFile($file);
            if (!$analysis->hasErrors()) {
                $analyses[] = $analysis;
            }
        }
        
        $duplicates = $phpAnalyzer->findDuplicateMethods($analyses);
        
        if (empty($duplicates)) {
            $this->info('No PHP duplicate methods found.');
            return;
        }
        
        $this->info("Found " . count($duplicates) . " PHP duplicate method patterns:");
        
        foreach ($duplicates as $duplicate) {
            $this->line('');
            $this->warn("🔄 Duplicate Methods (Similarity: {$duplicate->getSimilarityPercentage()}%)");
            $this->line("   Method 1: {$duplicate->getMethod1FullName()} ({$duplicate->getMethod1Location()})");
            $this->line("   Method 2: {$duplicate->getMethod2FullName()} ({$duplicate->getMethod2Location()})");
            $this->line("   Suggestion: {$duplicate->suggestion->description}");
            $this->line("   Effort: {$duplicate->suggestion->effort}");
            $this->line("   Lines saved: {$duplicate->suggestion->getEstimatedLinesSaved()}");
        }
    }

    private function analyzeJavaScriptDuplicates(): void
    {
        $this->info('Analyzing JavaScript files for duplicate functions...');
        
        $jsAnalyzer = new JavaScriptAnalyzer();
        $jsFiles = $this->getJavaScriptFiles();
        $analyses = [];
        
        foreach ($jsFiles as $file) {
            try {
                $analysis = $jsAnalyzer->parseFile($file);
                $analyses[] = $analysis;
            } catch (\Exception $e) {
                $this->warn("Skipping {$file}: " . $e->getMessage());
            }
        }
        
        $duplicates = $jsAnalyzer->findDuplicateFunctions($analyses);
        
        if (empty($duplicates)) {
            $this->info('No JavaScript duplicate functions found.');
            return;
        }
        
        $this->info("Found " . count($duplicates) . " JavaScript duplicate function patterns:");
        
        foreach ($duplicates as $duplicate) {
            $this->line('');
            $this->warn("🔄 Duplicate Function: {$duplicate['signature']}");
            foreach ($duplicate['occurrences'] as $occurrence) {
                $this->line("   Found in: {$occurrence['file']}");
            }
        }
    }

    private function analyzeCssDuplicates(): void
    {
        $this->info('Analyzing CSS files for duplicate rules...');
        
        $cssAnalyzer = new CssAnalyzer();
        $cssFiles = $this->getCssFiles();
        $analyses = [];
        
        foreach ($cssFiles as $file) {
            try {
                $analysis = $cssAnalyzer->parseFile($file);
                $analyses[] = $analysis;
            } catch (\Exception $e) {
                $this->warn("Skipping {$file}: " . $e->getMessage());
            }
        }
        
        $duplicates = $cssAnalyzer->findDuplicateRules($analyses);
        
        if (empty($duplicates)) {
            $this->info('No CSS duplicate rules found.');
            return;
        }
        
        $this->info("Found " . count($duplicates) . " CSS duplicate rule patterns:");
        
        foreach ($duplicates as $duplicate) {
            $this->line('');
            $this->warn("🔄 Duplicate CSS Rules");
            foreach ($duplicate['occurrences'] as $occurrence) {
                $rule = $occurrence['rule'];
                $this->line("   {$rule['selector']} in {$occurrence['file']}");
            }
        }
    }

    private function analyzeBladeDuplicates(): void
    {
        $this->info('Analyzing Blade templates for duplicate structures...');
        
        $bladeAnalyzer = new BladeAnalyzer();
        $bladeFiles = $this->getBladeFiles();
        $analyses = [];
        
        foreach ($bladeFiles as $file) {
            try {
                $analysis = $bladeAnalyzer->parseTemplate($file);
                $analyses[] = $analysis;
            } catch (\Exception $e) {
                $this->warn("Skipping {$file}: " . $e->getMessage());
            }
        }
        
        $duplicates = $bladeAnalyzer->findDuplicateStructures($analyses);
        $componentSuggestions = $bladeAnalyzer->extractComponentCandidates($analyses);
        
        if (empty($duplicates) && empty($componentSuggestions)) {
            $this->info('No Blade duplicate structures found.');
            return;
        }
        
        if (!empty($duplicates)) {
            $this->info("Found " . count($duplicates) . " Blade duplicate structure patterns:");
            
            foreach ($duplicates as $duplicate) {
                $this->line('');
                $this->warn("🔄 Duplicate Structure (Similarity: " . round($duplicate['similarity_score'] * 100) . "%)");
                foreach ($duplicate['occurrences'] as $occurrence) {
                    $this->line("   Found in: {$occurrence['file']}");
                }
            }
        }
        
        if (!empty($componentSuggestions)) {
            $this->line('');
            $this->info("💡 Component extraction suggestions:");
            
            foreach ($componentSuggestions as $suggestion) {
                $this->line('');
                $this->comment("Component: {$suggestion['suggested_name']}");
                $this->line("   Potential savings: {$suggestion['potential_savings']} duplicates");
                $this->line("   Files affected: " . count($suggestion['occurrences']));
            }
        }
    }

    private function analyzeAllDuplicates(): void
    {
        $this->info('Performing comprehensive duplicate analysis...');
        
        $crossFileDetector = new CrossFileDuplicateDetector(
            new JavaScriptAnalyzer(),
            new CssAnalyzer(),
            new BladeAnalyzer()
        );
        
        // Collect all analyses
        $jsAnalyses = $this->collectJavaScriptAnalyses();
        $cssAnalyses = $this->collectCssAnalyses();
        $bladeAnalyses = $this->collectBladeAnalyses();
        
        $report = $crossFileDetector->findAllDuplicates($jsAnalyses, $cssAnalyses, $bladeAnalyses);
        
        // Display summary
        $this->line('');
        $this->info('📊 Duplicate Analysis Summary');
        $this->line('================================');
        $this->line("Total duplicates found: {$report->getTotalDuplicatesFound()}");
        $this->line("JavaScript duplicates: " . count($report->jsDuplicates));
        $this->line("CSS duplicates: " . count($report->cssDuplicates));
        $this->line("Blade duplicates: " . count($report->bladeDuplicates));
        $this->line("Component suggestions: " . count($report->componentSuggestions));
        
        // Display executive summary
        $this->line('');
        $this->comment($report->generateExecutiveSummary());
        
        // Display top priority recommendations
        $highPriority = $report->getHighPriorityItems();
        if (!empty($highPriority)) {
            $this->line('');
            $this->info('🎯 High Priority Recommendations:');
            foreach (array_slice($highPriority, 0, 5) as $item) {
                $this->line("   • {$item['description']} (Priority: {$item['priority']})");
            }
        }
        
        // Display low effort items
        $lowEffort = $report->getLowEffortItems();
        if (!empty($lowEffort)) {
            $this->line('');
            $this->info('⚡ Quick Wins (Low Effort):');
            foreach (array_slice($lowEffort, 0, 3) as $item) {
                $this->line("   • {$item['description']}");
            }
        }
    }

    private function getPhpFiles(): array
    {
        return File::allFiles(app_path())
            ->filter(fn($file) => $file->getExtension() === 'php')
            ->map(fn($file) => $file->getPathname())
            ->toArray();
    }

    private function getJavaScriptFiles(): array
    {
        $files = [];
        
        if (File::exists(resource_path('js'))) {
            $jsFiles = File::allFiles(resource_path('js'));
            foreach ($jsFiles as $file) {
                if (in_array($file->getExtension(), ['js', 'ts', 'jsx', 'tsx'])) {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }

    private function getCssFiles(): array
    {
        $files = [];
        
        if (File::exists(resource_path('css'))) {
            $cssFiles = File::allFiles(resource_path('css'));
            foreach ($cssFiles as $file) {
                if (in_array($file->getExtension(), ['css', 'scss', 'sass'])) {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }

    private function getBladeFiles(): array
    {
        return File::allFiles(resource_path('views'))
            ->filter(fn($file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->map(fn($file) => $file->getPathname())
            ->toArray();
    }

    private function collectJavaScriptAnalyses(): array
    {
        $jsAnalyzer = new JavaScriptAnalyzer();
        $analyses = [];
        
        foreach ($this->getJavaScriptFiles() as $file) {
            try {
                $analyses[] = $jsAnalyzer->parseFile($file);
            } catch (\Exception $e) {
                // Skip files that can't be parsed
            }
        }
        
        return $analyses;
    }

    private function collectCssAnalyses(): array
    {
        $cssAnalyzer = new CssAnalyzer();
        $analyses = [];
        
        foreach ($this->getCssFiles() as $file) {
            try {
                $analyses[] = $cssAnalyzer->parseFile($file);
            } catch (\Exception $e) {
                // Skip files that can't be parsed
            }
        }
        
        return $analyses;
    }

    private function collectBladeAnalyses(): array
    {
        $bladeAnalyzer = new BladeAnalyzer();
        $analyses = [];
        
        foreach ($this->getBladeFiles() as $file) {
            try {
                $analyses[] = $bladeAnalyzer->parseTemplate($file);
            } catch (\Exception $e) {
                // Skip files that can't be parsed
            }
        }
        
        return $analyses;
    }
}