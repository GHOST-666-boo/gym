<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\OrphanedFileDetectorInterface;
use App\Services\Cleanup\Models\AssetFileAnalysis;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OrphanedFileDetector implements OrphanedFileDetectorInterface
{
    private array $fileReferences = [];
    private array $assetExtensions = [
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'],
        'fonts' => ['ttf', 'otf', 'woff', 'woff2', 'eot'],
        'documents' => ['pdf', 'doc', 'docx', 'txt'],
        'media' => ['mp4', 'mp3', 'avi', 'mov', 'wav'],
        'styles' => ['css', 'scss', 'sass', 'less'],
        'scripts' => ['js', 'ts', 'jsx', 'tsx'],
    ];
    
    private array $protectedPaths = [
        'vendor/',
        'node_modules/',
        '.git/',
        'storage/framework/',
        'storage/logs/',
        'bootstrap/cache/',
    ];

    public function scanCodebaseReferences(): array
    {
        $this->fileReferences = [];
        
        // Scan PHP files for asset references
        $this->scanPhpFiles();
        
        // Scan Blade templates for asset references
        $this->scanBladeTemplates();
        
        // Scan CSS files for asset references
        $this->scanCssFiles();
        
        // Scan JavaScript files for asset references
        $this->scanJavaScriptFiles();
        
        // Scan configuration files
        $this->scanConfigFiles();
        
        return $this->fileReferences;
    }

    public function detectAssetUsage(array $assetPaths): array
    {
        $assetAnalyses = [];
        
        foreach ($assetPaths as $assetPath) {
            if (File::isDirectory($assetPath)) {
                // Analyze all files in the directory
                $files = File::allFiles($assetPath);
                foreach ($files as $file) {
                    if ($this->isAssetFile($file->getPathname())) {
                        $assetAnalyses[] = $this->analyzeAssetFile($file->getPathname());
                    }
                }
            } elseif (File::exists($assetPath) && $this->isAssetFile($assetPath)) {
                // Analyze single file
                $assetAnalyses[] = $this->analyzeAssetFile($assetPath);
            }
        }
        
        return $assetAnalyses;
    }

    public function findOrphanedFiles(): array
    {
        $this->scanCodebaseReferences();
        
        $orphanedFiles = [];
        $allAssets = $this->getAllAssetFiles();
        
        foreach ($allAssets as $assetPath) {
            if (!$this->isFileReferenced($assetPath) && $this->validateSafeDeletion($assetPath)) {
                $orphanedFiles[] = $this->analyzeAssetFile($assetPath);
            }
        }
        
        return $orphanedFiles;
    }

    public function validateSafeDeletion(string $filePath): bool
    {
        // Don't delete protected paths
        foreach ($this->protectedPaths as $protectedPath) {
            if (Str::startsWith($filePath, $protectedPath)) {
                return false;
            }
        }
        
        // Don't delete system files
        $systemFiles = [
            'public/.htaccess',
            'public/index.php',
            'public/robots.txt',
            'public/favicon.ico',
        ];
        
        if (in_array($filePath, $systemFiles)) {
            return false;
        }
        
        // Don't delete files that might be dynamically referenced
        if ($this->mightBeDynamicallyReferenced($filePath)) {
            return false;
        }
        
        return true;
    }

    public function analyzeAssetFile(string $filePath): AssetFileAnalysis
    {
        if (!File::exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }
        
        $analysis = new AssetFileAnalysis($filePath, [
            'type' => $this->getAssetType($filePath),
            'size' => File::size($filePath),
            'lastModified' => date('Y-m-d H:i:s', File::lastModified($filePath)),
        ]);
        
        // Find references to this file
        $references = $this->findReferencesToFile($filePath);
        foreach ($references as $reference) {
            $analysis->addReference($reference);
        }
        
        return $analysis;
    }

    private function scanPhpFiles(): void
    {
        $phpFiles = File::allFiles(app_path());
        
        foreach ($phpFiles as $file) {
            if ($file->getExtension() === 'php') {
                $this->scanFileForAssetReferences($file->getPathname());
            }
        }
    }

    private function scanBladeTemplates(): void
    {
        $bladeFiles = File::allFiles(resource_path('views'));
        
        foreach ($bladeFiles as $file) {
            if ($file->getExtension() === 'php') {
                $this->scanFileForAssetReferences($file->getPathname());
            }
        }
    }

    private function scanCssFiles(): void
    {
        $cssDirectories = [
            public_path('css'),
            resource_path('css'),
        ];
        
        foreach ($cssDirectories as $directory) {
            if (File::isDirectory($directory)) {
                $cssFiles = File::allFiles($directory);
                foreach ($cssFiles as $file) {
                    if (in_array($file->getExtension(), ['css', 'scss', 'sass', 'less'])) {
                        $this->scanFileForAssetReferences($file->getPathname());
                    }
                }
            }
        }
    }

    private function scanJavaScriptFiles(): void
    {
        $jsDirectories = [
            public_path('js'),
            resource_path('js'),
        ];
        
        foreach ($jsDirectories as $directory) {
            if (File::isDirectory($directory)) {
                $jsFiles = File::allFiles($directory);
                foreach ($jsFiles as $file) {
                    if (in_array($file->getExtension(), ['js', 'ts', 'jsx', 'tsx'])) {
                        $this->scanFileForAssetReferences($file->getPathname());
                    }
                }
            }
        }
    }

    private function scanConfigFiles(): void
    {
        $configFiles = File::allFiles(config_path());
        
        foreach ($configFiles as $file) {
            if ($file->getExtension() === 'php') {
                $this->scanFileForAssetReferences($file->getPathname());
            }
        }
    }

    private function scanFileForAssetReferences(string $filePath): void
    {
        $content = File::get($filePath);
        
        // Common asset reference patterns
        $patterns = [
            // URL and asset helper functions
            '/(?:asset|url|mix)\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*\)/',
            // Direct file paths in quotes
            '/[\'"]([^\'\"]*\.(jpg|jpeg|png|gif|svg|webp|ico|ttf|otf|woff|woff2|eot|pdf|css|js|mp4|mp3))[\'"]/',
            // CSS url() references
            '/url\s*\(\s*[\'"]?([^\'\")\s]+)[\'"]?\s*\)/',
            // Blade asset directives
            '/@(?:asset|mix)\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*\)/',
            // Image src attributes
            '/src\s*=\s*[\'"]([^\'\"]+)[\'"]/',
            // Link href attributes
            '/href\s*=\s*[\'"]([^\'\"]+)[\'"]/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $this->addFileReference($match, $filePath);
                }
            }
        }
    }

    private function addFileReference(string $referencedFile, string $referencingFile): void
    {
        // Normalize the path
        $referencedFile = $this->normalizePath($referencedFile);
        
        if (!isset($this->fileReferences[$referencedFile])) {
            $this->fileReferences[$referencedFile] = [];
        }
        
        if (!in_array($referencingFile, $this->fileReferences[$referencedFile])) {
            $this->fileReferences[$referencedFile][] = $referencingFile;
        }
    }

    private function normalizePath(string $path): string
    {
        // Don't modify external URLs or data URIs
        if (Str::startsWith($path, ['http://', 'https://', 'data:', '//'])) {
            return $path;
        }
        
        // Remove leading slashes and normalize
        $path = ltrim($path, '/');
        
        // If it doesn't start with public/, assume it's in public/
        if (!Str::startsWith($path, 'public/')) {
            $path = 'public/' . $path;
        }
        
        return $path;
    }

    private function getAllAssetFiles(): array
    {
        $assetFiles = [];
        $directories = [
            public_path(),
            resource_path(),
            storage_path('app/public'),
        ];
        
        foreach ($directories as $directory) {
            if (File::isDirectory($directory)) {
                $files = File::allFiles($directory);
                foreach ($files as $file) {
                    if ($this->isAssetFile($file->getPathname())) {
                        $assetFiles[] = $file->getPathname();
                    }
                }
            }
        }
        
        return $assetFiles;
    }

    private function isAssetFile(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        foreach ($this->assetExtensions as $extensions) {
            if (in_array($extension, $extensions)) {
                return true;
            }
        }
        
        return false;
    }

    private function getAssetType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        foreach ($this->assetExtensions as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }
        
        return 'unknown';
    }

    private function isFileReferenced(string $filePath): bool
    {
        $normalizedPath = $this->normalizePath($filePath);
        return isset($this->fileReferences[$normalizedPath]) && !empty($this->fileReferences[$normalizedPath]);
    }

    private function findReferencesToFile(string $filePath): array
    {
        $normalizedPath = $this->normalizePath($filePath);
        return $this->fileReferences[$normalizedPath] ?? [];
    }

    private function mightBeDynamicallyReferenced(string $filePath): bool
    {
        // Check if file might be referenced dynamically
        $filename = basename($filePath);
        
        // Files that might be loaded dynamically
        $dynamicPatterns = [
            '/^lang_\w+\.js$/',  // Language files
            '/^theme_\w+\.css$/', // Theme files
            '/^\d+\.jpg$/',       // Numbered images that might be in loops
            '/^user_\d+\./',      // User-specific files
        ];
        
        foreach ($dynamicPatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                return true;
            }
        }
        
        return false;
    }
}