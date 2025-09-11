<?php

namespace App\Services\Cleanup\Contracts;

interface TestValidatorInterface
{
    /**
     * Run automated tests to validate functionality after cleanup
     */
    public function runValidationTests(): array;

    /**
     * Verify functionality after code removal
     */
    public function verifyFunctionality(array $removedElements): bool;

    /**
     * Check for dynamic code usage (reflection, etc.)
     */
    public function checkDynamicUsage(array $codeElements): array;

    /**
     * Run specific test suite
     */
    public function runTestSuite(string $suite): array;

    /**
     * Validate that critical functionality still works
     */
    public function validateCriticalPaths(): bool;

    /**
     * Check for runtime errors after cleanup
     */
    public function checkRuntimeErrors(): array;

    /**
     * Validate database integrity after cleanup
     */
    public function validateDatabaseIntegrity(): bool;

    /**
     * Get test results summary
     */
    public function getTestResults(): array;
}