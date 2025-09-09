<?php

namespace App\Services\Cleanup\Contracts;

use App\Services\Cleanup\Models\RouteAnalysis;

interface LaravelAnalyzerInterface
{
    /**
     * Parse route definitions from Laravel route files
     *
     * @param array $routeFiles Array of route file paths
     * @return array Array of RouteAnalysis objects
     */
    public function parseRouteDefinitions(array $routeFiles): array;
    
    /**
     * Analyze controller method usage across the application
     *
     * @param array $controllerPaths Array of controller directory paths
     * @return array Array of controller method usage data
     */
    public function analyzeControllerUsage(array $controllerPaths): array;
    
    /**
     * Find unused routes in the application
     *
     * @param array $routes Array of RouteAnalysis objects
     * @return array Array of unused routes
     */
    public function findUnusedRoutes(array $routes): array;
    
    /**
     * Find unused controller methods
     *
     * @param array $controllerMethods Array of controller methods
     * @param array $routes Array of RouteAnalysis objects
     * @return array Array of unused controller methods
     */
    public function findUnusedControllerMethods(array $controllerMethods, array $routes): array;
    
    /**
     * Analyze Eloquent model usage across the application
     *
     * @param array $modelPaths Array of model directory paths
     * @return array Array of model usage data
     */
    public function analyzeModelUsage(array $modelPaths): array;
    
    /**
     * Analyze migration files for unused migrations
     *
     * @param string $migrationPath Path to migrations directory
     * @return array Array of migration analysis data
     */
    public function analyzeMigrations(string $migrationPath): array;
    
    /**
     * Find unused Eloquent models
     *
     * @param array $models Array of model data
     * @param array $controllerFiles Array of controller file paths
     * @param array $bladeFiles Array of blade template file paths
     * @return array Array of unused models
     */
    public function findUnusedModels(array $models, array $controllerFiles, array $bladeFiles): array;
    
    /**
     * Find unused migration files
     *
     * @param array $migrations Array of migration data
     * @return array Array of unused migrations
     */
    public function findUnusedMigrations(array $migrations): array;
}