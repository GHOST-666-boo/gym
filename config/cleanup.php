<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Code Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the code cleanup and
    | optimization system. These settings control how the cleanup process
    | analyzes and modifies your codebase.
    |
    */

    'default_config' => [
        'dry_run' => env('CLEANUP_DRY_RUN', true),
        'create_backup' => env('CLEANUP_CREATE_BACKUP', true),
        'run_tests' => env('CLEANUP_RUN_TESTS', true),
        'batch_size' => env('CLEANUP_BATCH_SIZE', 50),
        'max_file_size' => env('CLEANUP_MAX_FILE_SIZE', 1048576), // 1MB
    ],

    'file_types' => [
        'include' => ['php', 'js', 'css', 'blade.php', 'vue'],
        'exclude_extensions' => ['min.js', 'min.css'],
    ],

    'exclude_paths' => [
        'vendor/',
        'node_modules/',
        'storage/framework/',
        'bootstrap/cache/',
        '.git/',
        'tests/fixtures/',
    ],

    'cleanup_operations' => [
        'remove_unused_imports' => env('CLEANUP_REMOVE_IMPORTS', true),
        'remove_unused_methods' => env('CLEANUP_REMOVE_METHODS', true),
        'remove_unused_variables' => env('CLEANUP_REMOVE_VARIABLES', true),
        'refactor_duplicates' => env('CLEANUP_REFACTOR_DUPLICATES', true),
        'create_components' => env('CLEANUP_CREATE_COMPONENTS', true),
        'remove_orphaned_files' => env('CLEANUP_REMOVE_FILES', false),
    ],

    'safety_checks' => [
        'require_tests_pass' => true,
        'check_dynamic_references' => true,
        'validate_laravel_conventions' => true,
        'preserve_public_methods' => true,
    ],

    'reporting' => [
        'generate_detailed_report' => true,
        'include_performance_metrics' => true,
        'include_maintenance_recommendations' => true,
        'export_formats' => ['json', 'html'],
    ],

    'php_analysis' => [
        'parse_docblocks' => true,
        'check_inheritance' => true,
        'analyze_traits' => true,
        'detect_magic_methods' => true,
    ],

    'javascript_analysis' => [
        'parse_es6_modules' => true,
        'check_vue_components' => true,
        'analyze_jquery_usage' => true,
    ],

    'blade_analysis' => [
        'check_component_usage' => true,
        'analyze_slot_usage' => true,
        'detect_duplicate_structures' => true,
        'suggest_componentization' => true,
    ],
];