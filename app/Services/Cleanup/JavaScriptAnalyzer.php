<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\JavaScriptAnalyzerInterface;
use App\Services\Cleanup\Models\JsFileAnalysis;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class JavaScriptAnalyzer implements JavaScriptAnalyzerInterface
{
    private string $nodeScriptPath;
    
    public function __construct()
    {
        $this->nodeScriptPath = base_path('scripts/js-analyzer.cjs');
        $this->ensureNodeScriptExists();
    }
    
    public function parseFile(string $filePath): JsFileAnalysis
    {
        if (!File::exists($filePath)) {
            throw new \InvalidArgumentException("JavaScript file not found: {$filePath}");
        }
        
        try {
            $analysisData = $this->runNodeAnalysis($filePath);
            
            return new JsFileAnalysis($filePath, [
                'imports' => $analysisData['imports'] ?? [],
                'functions' => $analysisData['functions'] ?? [],
                'variables' => $analysisData['variables'] ?? [],
                'exports' => $analysisData['exports'] ?? [],
                'dependencies' => $analysisData['dependencies'] ?? [],
                'ast' => $analysisData['ast'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to parse JavaScript file: {$filePath}", [
                'error' => $e->getMessage()
            ]);
            
            // Return empty analysis on failure
            return new JsFileAnalysis($filePath);
        }
    }
    
    public function findUnusedImports(JsFileAnalysis $analysis): array
    {
        $unusedImports = [];
        
        foreach ($analysis->imports as $import) {
            $importName = $import['name'] ?? '';
            
            if (!$this->isIdentifierUsedInAnalysis($importName, $analysis)) {
                $unusedImports[] = $import;
            }
        }
        
        return $unusedImports;
    }
    
    public function findUnusedVariables(JsFileAnalysis $analysis): array
    {
        $unusedVariables = [];
        
        foreach ($analysis->variables as $variable) {
            $variableName = $variable['name'] ?? '';
            $isUsed = false;
            
            // Check if variable is used in the analysis
            if ($this->isIdentifierUsedInAnalysis($variableName, $analysis)) {
                $isUsed = true;
            }
            
            // Check if variable is exported
            if (!$isUsed) {
                foreach ($analysis->exports as $export) {
                    if (($export['name'] ?? '') === $variableName) {
                        $isUsed = true;
                        break;
                    }
                }
            }
            
            if (!$isUsed) {
                $unusedVariables[] = $variable;
            }
        }
        
        return $unusedVariables;
    }
    
    public function findDuplicateFunctions(array $analyses): array
    {
        $duplicates = [];
        $functionSignatures = [];
        
        foreach ($analyses as $analysis) {
            if (!$analysis instanceof JsFileAnalysis) {
                continue;
            }
            
            foreach ($analysis->functions as $function) {
                $signature = $this->generateFunctionSignature($function);
                
                if (!isset($functionSignatures[$signature])) {
                    $functionSignatures[$signature] = [];
                }
                
                $functionSignatures[$signature][] = [
                    'file' => $analysis->filePath,
                    'function' => $function
                ];
            }
        }
        
        // Find signatures with multiple occurrences
        foreach ($functionSignatures as $signature => $occurrences) {
            if (count($occurrences) > 1) {
                $duplicates[] = [
                    'signature' => $signature,
                    'occurrences' => $occurrences
                ];
            }
        }
        
        return $duplicates;
    }
    
    private function runNodeAnalysis(string $filePath): array
    {
        $command = "node \"{$this->nodeScriptPath}\" \"{$filePath}\"";
        
        $output = shell_exec($command);
        
        if ($output === null) {
            throw new \RuntimeException("Failed to execute Node.js analysis script");
        }
        
        $result = json_decode($output, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON response from Node.js script: " . json_last_error_msg());
        }
        
        return $result;
    }
    
    private function isIdentifierUsedInCode(string $identifier, string $code): bool
    {
        // Remove comments to avoid false positives
        $codeWithoutComments = preg_replace('/\/\/.*$/m', '', $code);
        $codeWithoutComments = preg_replace('/\/\*.*?\*\//s', '', $codeWithoutComments);
        
        // Special case for React - if there's JSX in the code, React is implicitly used
        if ($identifier === 'React' && preg_match('/<[a-zA-Z]/', $codeWithoutComments)) {
            return true;
        }
        
        // Simple regex-based check for identifier usage
        $patterns = [
            '/\b' . preg_quote($identifier, '/') . '\s*\(/',  // Function call: identifier(
            '/\b' . preg_quote($identifier, '/') . '\s*\./',  // Property access: identifier.
            '/\b' . preg_quote($identifier, '/') . '\s*\[/',  // Array access: identifier[
            '/<' . preg_quote($identifier, '/') . '\b/',      // JSX component: <identifier
            '/\{\s*' . preg_quote($identifier, '/') . '\s*\}/', // JSX expression: {identifier}
            '/\b' . preg_quote($identifier, '/') . '\s*[,;)]/', // Used as parameter or argument
            '/\(\s*' . preg_quote($identifier, '/') . '\s*[,)]/', // Function parameter
            '/console\.\w+\s*\(\s*' . preg_quote($identifier, '/') . '\s*[,)]/', // console.log(identifier)
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $codeWithoutComments)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isIdentifierUsedInAnalysis(string $identifier, JsFileAnalysis $analysis): bool
    {
        // Check if identifier is used in any function body
        foreach ($analysis->functions as $function) {
            if ($this->isIdentifierUsedInCode($identifier, $function['body'] ?? '')) {
                return true;
            }
        }
        
        // Check if identifier is used in variable values
        foreach ($analysis->variables as $variable) {
            if ($this->isIdentifierUsedInCode($identifier, $variable['value'] ?? '')) {
                return true;
            }
        }
        
        return false;
    }
    
    private function generateFunctionSignature(array $function): string
    {
        $name = $function['name'] ?? 'anonymous';
        $params = $function['params'] ?? [];
        $paramString = implode(',', array_map(function($param) {
            return $param['name'] ?? '';
        }, $params));
        
        return "{$name}({$paramString})";
    }
    
    private function ensureNodeScriptExists(): void
    {
        $scriptDir = dirname($this->nodeScriptPath);
        
        if (!File::exists($scriptDir)) {
            File::makeDirectory($scriptDir, 0755, true);
        }
        
        if (!File::exists($this->nodeScriptPath)) {
            $this->createNodeScript();
        }
    }
    
    private function createNodeScript(): void
    {
        $scriptContent = $this->getNodeScriptContent();
        File::put($this->nodeScriptPath, $scriptContent);
    }
    
    private function getNodeScriptContent(): string
    {
        return <<<'JS'
const fs = require('fs');
const parser = require('@babel/parser');
const traverse = require('@babel/traverse').default;

function analyzeJavaScript(filePath) {
    try {
        const code = fs.readFileSync(filePath, 'utf8');
        
        const ast = parser.parse(code, {
            sourceType: 'module',
            allowImportExportEverywhere: true,
            allowReturnOutsideFunction: true,
            plugins: [
                'jsx',
                'typescript',
                'decorators-legacy',
                'classProperties',
                'objectRestSpread',
                'asyncGenerators',
                'functionBind',
                'exportDefaultFrom',
                'exportNamespaceFrom',
                'dynamicImport',
                'nullishCoalescingOperator',
                'optionalChaining'
            ]
        });
        
        const analysis = {
            imports: [],
            functions: [],
            variables: [],
            exports: [],
            dependencies: []
        };
        
        traverse(ast, {
            ImportDeclaration(path) {
                const source = path.node.source.value;
                
                path.node.specifiers.forEach(spec => {
                    let importName = '';
                    let importType = 'default';
                    
                    if (spec.type === 'ImportDefaultSpecifier') {
                        importName = spec.local.name;
                        importType = 'default';
                    } else if (spec.type === 'ImportSpecifier') {
                        importName = spec.local.name;
                        importType = 'named';
                    } else if (spec.type === 'ImportNamespaceSpecifier') {
                        importName = spec.local.name;
                        importType = 'namespace';
                    }
                    
                    analysis.imports.push({
                        name: importName,
                        source: source,
                        type: importType,
                        line: path.node.loc?.start.line || 0
                    });
                });
                
                analysis.dependencies.push(source);
            },
            
            FunctionDeclaration(path) {
                const func = path.node;
                analysis.functions.push({
                    name: func.id?.name || 'anonymous',
                    params: func.params.map(param => ({
                        name: param.name || param.left?.name || 'unknown'
                    })),
                    line: func.loc?.start.line || 0,
                    body: code.substring(func.start, func.end)
                });
            },
            
            ArrowFunctionExpression(path) {
                const func = path.node;
                const parent = path.parent;
                let name = 'anonymous';
                
                if (parent.type === 'VariableDeclarator' && parent.id.name) {
                    name = parent.id.name;
                } else if (parent.type === 'AssignmentExpression' && parent.left.name) {
                    name = parent.left.name;
                }
                
                analysis.functions.push({
                    name: name,
                    params: func.params.map(param => ({
                        name: param.name || param.left?.name || 'unknown'
                    })),
                    line: func.loc?.start.line || 0,
                    body: code.substring(func.start, func.end)
                });
            },
            
            VariableDeclarator(path) {
                const node = path.node;
                if (node.id.name) {
                    analysis.variables.push({
                        name: node.id.name,
                        line: node.loc?.start.line || 0,
                        value: node.init ? code.substring(node.init.start, node.init.end) : ''
                    });
                }
            },
            
            ExportDefaultDeclaration(path) {
                const node = path.node;
                let name = 'default';
                
                if (node.declaration.name) {
                    name = node.declaration.name;
                } else if (node.declaration.id?.name) {
                    name = node.declaration.id.name;
                }
                
                analysis.exports.push({
                    name: name,
                    type: 'default',
                    line: node.loc?.start.line || 0
                });
            },
            
            ExportNamedDeclaration(path) {
                const node = path.node;
                
                if (node.specifiers) {
                    node.specifiers.forEach(spec => {
                        analysis.exports.push({
                            name: spec.exported.name,
                            type: 'named',
                            line: node.loc?.start.line || 0
                        });
                    });
                }
                
                if (node.declaration) {
                    if (node.declaration.id?.name) {
                        analysis.exports.push({
                            name: node.declaration.id.name,
                            type: 'named',
                            line: node.loc?.start.line || 0
                        });
                    }
                }
            }
        });
        
        return analysis;
        
    } catch (error) {
        return {
            error: error.message,
            imports: [],
            functions: [],
            variables: [],
            exports: [],
            dependencies: []
        };
    }
}

// Main execution
const filePath = process.argv[2];
if (!filePath) {
    console.error('Usage: node js-analyzer.js <file-path>');
    process.exit(1);
}

const result = analyzeJavaScript(filePath);
console.log(JSON.stringify(result, null, 2));
JS;
    }
}