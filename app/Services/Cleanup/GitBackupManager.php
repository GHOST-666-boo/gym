<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\BackupManagerInterface;
use App\Services\Cleanup\Models\BackupRecord;
use App\Services\Cleanup\Models\CheckpointRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GitBackupManager implements BackupManagerInterface
{
    private array $backups = [];
    private array $checkpoints = [];
    private string $sessionId;

    public function __construct()
    {
        $this->sessionId = Str::uuid()->toString();
        $this->ensureGitRepository();
    }

    public function createBackup(string $description = null): string
    {
        $backupId = 'cleanup-backup-' . date('Y-m-d-H-i-s') . '-' . Str::random(8);
        $description = $description ?? 'Code cleanup backup created at ' . now()->toDateTimeString();

        try {
            // Ensure all changes are staged
            $this->executeGitCommand('git add -A');
            
            // Create commit with backup description
            $escapedDescription = escapeshellarg($description);
            $this->executeGitCommand("git commit -m $escapedDescription --allow-empty");
            
            // Get the commit hash
            $commitHash = trim($this->executeGitCommand('git rev-parse HEAD'));
            
            // Create backup record
            $backup = new BackupRecord([
                'id' => $backupId,
                'commit_hash' => $commitHash,
                'description' => $description,
                'created_at' => now(),
                'session_id' => $this->sessionId
            ]);
            
            $this->backups[$backupId] = $backup;
            
            Log::info("Backup created: {$backupId} at commit {$commitHash}");
            
            return $backupId;
        } catch (\Exception $e) {
            Log::error("Failed to create backup: " . $e->getMessage());
            throw new RuntimeException("Failed to create backup: " . $e->getMessage());
        }
    }

    public function rollback(string $backupId): bool
    {
        if (!isset($this->backups[$backupId])) {
            throw new RuntimeException("Backup not found: {$backupId}");
        }

        $backup = $this->backups[$backupId];
        
        try {
            // Check if we can rollback safely
            if (!$this->canRollback($backupId)) {
                throw new RuntimeException("Cannot rollback to {$backupId} - uncommitted changes exist");
            }
            
            // Reset to the backup commit
            $this->executeGitCommand("git reset --hard {$backup->commit_hash}");
            
            Log::info("Successfully rolled back to backup: {$backupId}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to rollback to backup {$backupId}: " . $e->getMessage());
            throw new RuntimeException("Failed to rollback: " . $e->getMessage());
        }
    }

    public function getBackups(): array
    {
        return array_values($this->backups);
    }

    public function deleteBackup(string $backupId): bool
    {
        if (!isset($this->backups[$backupId])) {
            return false;
        }

        unset($this->backups[$backupId]);
        Log::info("Backup deleted: {$backupId}");
        
        return true;
    }

    public function createCheckpoint(string $operation, array $metadata = []): string
    {
        $checkpointId = 'checkpoint-' . date('H-i-s') . '-' . Str::random(6);
        
        try {
            // Stage current changes
            $this->executeGitCommand('git add -A');
            
            // Create checkpoint commit
            $message = "Checkpoint: {$operation}";
            $escapedMessage = escapeshellarg($message);
            $this->executeGitCommand("git commit -m $escapedMessage --allow-empty");
            
            // Get commit hash
            $commitHash = trim($this->executeGitCommand('git rev-parse HEAD'));
            
            // Create checkpoint record
            $checkpoint = new CheckpointRecord([
                'id' => $checkpointId,
                'commit_hash' => $commitHash,
                'operation' => $operation,
                'metadata' => $metadata,
                'created_at' => now(),
                'session_id' => $this->sessionId
            ]);
            
            $this->checkpoints[$checkpointId] = $checkpoint;
            
            Log::info("Checkpoint created: {$checkpointId} for operation: {$operation}");
            
            return $checkpointId;
        } catch (\Exception $e) {
            Log::error("Failed to create checkpoint for {$operation}: " . $e->getMessage());
            throw new RuntimeException("Failed to create checkpoint: " . $e->getMessage());
        }
    }

    public function rollbackToCheckpoint(string $checkpointId): bool
    {
        if (!isset($this->checkpoints[$checkpointId])) {
            throw new RuntimeException("Checkpoint not found: {$checkpointId}");
        }

        $checkpoint = $this->checkpoints[$checkpointId];
        
        try {
            // Reset to checkpoint commit
            $this->executeGitCommand("git reset --hard {$checkpoint->commit_hash}");
            
            // Remove checkpoints created after this one
            $this->cleanupCheckpointsAfter($checkpoint->created_at);
            
            Log::info("Successfully rolled back to checkpoint: {$checkpointId}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to rollback to checkpoint {$checkpointId}: " . $e->getMessage());
            throw new RuntimeException("Failed to rollback to checkpoint: " . $e->getMessage());
        }
    }

    public function getCheckpoints(): array
    {
        return array_values($this->checkpoints);
    }

    public function canRollback(string $backupId): bool
    {
        try {
            // Check if there are uncommitted changes
            $status = $this->executeGitCommand('git status --porcelain');
            
            if (!empty(trim($status))) {
                return false;
            }
            
            // Check if backup exists
            if (!isset($this->backups[$backupId])) {
                return false;
            }
            
            // Verify commit exists
            $backup = $this->backups[$backupId];
            $this->executeGitCommand("git cat-file -e {$backup->commit_hash}");
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function cleanup(int $keepDays = 7): int
    {
        $cutoffDate = now()->subDays($keepDays);
        $cleaned = 0;
        
        // Clean up old backups
        foreach ($this->backups as $id => $backup) {
            if ($backup->created_at->lt($cutoffDate)) {
                unset($this->backups[$id]);
                $cleaned++;
            }
        }
        
        // Clean up old checkpoints
        foreach ($this->checkpoints as $id => $checkpoint) {
            if ($checkpoint->created_at->lt($cutoffDate)) {
                unset($this->checkpoints[$id]);
                $cleaned++;
            }
        }
        
        Log::info("Cleaned up {$cleaned} old backups and checkpoints");
        
        return $cleaned;
    }

    private function ensureGitRepository(): void
    {
        try {
            $this->executeGitCommand('git rev-parse --git-dir');
        } catch (\Exception $e) {
            throw new RuntimeException('Not a git repository. Git is required for backup functionality.');
        }
    }

    private function executeGitCommand(string $command): string
    {
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new RuntimeException("Git command failed: {$command}. Output: " . implode("\n", $output));
        }
        
        return implode("\n", $output);
    }

    private function cleanupCheckpointsAfter(\Carbon\Carbon $date): void
    {
        foreach ($this->checkpoints as $id => $checkpoint) {
            if ($checkpoint->created_at->gt($date)) {
                unset($this->checkpoints[$id]);
            }
        }
    }
}