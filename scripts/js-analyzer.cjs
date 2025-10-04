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