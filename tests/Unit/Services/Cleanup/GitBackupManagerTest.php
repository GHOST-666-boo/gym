<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\GitBackupManager;
use App\Services\Cleanup\Models\BackupRecord;
use App\Services\Cleanup\Models\CheckpointRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use RuntimeException;

class GitBackupManagerTest extends TestCase
{
    use RefreshDatabase;

    private GitBackupManager $backupManager;
    private string $testRepoPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary git repository for testing
        $this->testRepoPath = sys_get_temp_dir() . '/cleanup_test_repo_' . uniqid();
        mkdir($this->testRepoPath);
        
        // Initialize git repo
        $this->execInPath("git init", $this->testRepoPath);
        $this->execInPath("git config user.email \"test@example.com\"", $this->testRepoPath);
        $this->execInPath("git config user.name \"Test User\"", $this->testRepoPath);
        
        // Create initial commit
        file_put_contents($this->testRepoPath . '/test.txt', 'initial content');
        $this->execInPath("git add test.txt", $this->testRepoPath);
        $this->execInPath("git commit -m \"Initial commit\"", $this->testRepoPath);
        
        // Change to test directory
        chdir($this->testRepoPath);
        
        $this->backupManager = new GitBackupManager();
    }

    private function execInPath(string $command, string $path): void
    {
        $originalDir = getcwd();
        chdir($path);
        exec($command);
        chdir($originalDir);
    }

    protected function tearDown(): void
    {
        // Clean up test repository
        if (is_dir($this->testRepoPath)) {
            if (PHP_OS_FAMILY === 'Windows') {
                exec("rmdir /s /q \"{$this->testRepoPath}\"");
            } else {
                exec("rm -rf {$this->testRepoPath}");
            }
        }
        
        parent::tearDown();
    }

    public function test_can_create_backup()
    {
        // Arrange
        file_put_contents('test_file.php', '<?php echo "test";');
        
        // Act
        $backupId = $this->backupManager->createBackup('Test backup');
        
        // Assert
        $this->assertNotEmpty($backupId);
        $this->assertStringContainsString('cleanup-backup-', $backupId);
        
        $backups = $this->backupManager->getBackups();
        $this->assertCount(1, $backups);
        $this->assertEquals($backupId, $backups[0]->id);
        $this->assertEquals('Test backup', $backups[0]->description);
    }

    public function test_can_rollback_to_backup()
    {
        // Arrange
        file_put_contents('test_file.php', '<?php echo "original";');
        $backupId = $this->backupManager->createBackup('Original state');
        
        // Modify file after backup
        file_put_contents('test_file.php', '<?php echo "modified";');
        exec('git add test_file.php');
        exec('git commit -m "Modified file"');
        
        // Act
        $result = $this->backupManager->rollback($backupId);
        
        // Assert
        $this->assertTrue($result);
        $this->assertEquals('<?php echo "original";', file_get_contents('test_file.php'));
    }

    public function test_rollback_fails_with_uncommitted_changes()
    {
        // Arrange
        $backupId = $this->backupManager->createBackup('Test backup');
        file_put_contents('uncommitted.php', '<?php echo "uncommitted";');
        
        // Act & Assert
        $this->assertFalse($this->backupManager->canRollback($backupId));
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot rollback');
        $this->backupManager->rollback($backupId);
    }

    public function test_can_create_checkpoint()
    {
        // Arrange
        $metadata = ['files_modified' => 3, 'operation_type' => 'remove_unused_imports'];
        
        // Act
        $checkpointId = $this->backupManager->createCheckpoint('Remove unused imports', $metadata);
        
        // Assert
        $this->assertNotEmpty($checkpointId);
        $this->assertStringContainsString('checkpoint-', $checkpointId);
        
        $checkpoints = $this->backupManager->getCheckpoints();
        $this->assertCount(1, $checkpoints);
        $this->assertEquals($checkpointId, $checkpoints[0]->id);
        $this->assertEquals('Remove unused imports', $checkpoints[0]->operation);
        $this->assertEquals($metadata, $checkpoints[0]->metadata);
    }

    public function test_can_rollback_to_checkpoint()
    {
        // Arrange
        file_put_contents('checkpoint_test.php', '<?php echo "before checkpoint";');
        $checkpointId = $this->backupManager->createCheckpoint('Test checkpoint');
        
        // Modify file after checkpoint
        file_put_contents('checkpoint_test.php', '<?php echo "after checkpoint";');
        exec('git add checkpoint_test.php');
        exec('git commit -m "After checkpoint"');
        
        // Act
        $result = $this->backupManager->rollbackToCheckpoint($checkpointId);
        
        // Assert
        $this->assertTrue($result);
        $this->assertEquals('<?php echo "before checkpoint";', file_get_contents('checkpoint_test.php'));
    }

    public function test_rollback_to_nonexistent_backup_throws_exception()
    {
        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Backup not found');
        $this->backupManager->rollback('nonexistent-backup-id');
    }

    public function test_rollback_to_nonexistent_checkpoint_throws_exception()
    {
        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Checkpoint not found');
        $this->backupManager->rollbackToCheckpoint('nonexistent-checkpoint-id');
    }

    public function test_can_delete_backup()
    {
        // Arrange
        $backupId = $this->backupManager->createBackup('Test backup');
        $this->assertCount(1, $this->backupManager->getBackups());
        
        // Act
        $result = $this->backupManager->deleteBackup($backupId);
        
        // Assert
        $this->assertTrue($result);
        $this->assertCount(0, $this->backupManager->getBackups());
    }

    public function test_delete_nonexistent_backup_returns_false()
    {
        // Act
        $result = $this->backupManager->deleteBackup('nonexistent-backup-id');
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_can_cleanup_old_backups_and_checkpoints()
    {
        // Arrange - Create backups and checkpoints
        $backupId1 = $this->backupManager->createBackup('Old backup');
        $checkpointId1 = $this->backupManager->createCheckpoint('Old checkpoint');
        
        // Simulate old timestamps by modifying the records
        $backups = $this->backupManager->getBackups();
        $checkpoints = $this->backupManager->getCheckpoints();
        
        // Use reflection to modify created_at timestamps
        $reflection = new \ReflectionClass($this->backupManager);
        $backupsProperty = $reflection->getProperty('backups');
        $backupsProperty->setAccessible(true);
        $checkpointsProperty = $reflection->getProperty('checkpoints');
        $checkpointsProperty->setAccessible(true);
        
        $backupsArray = $backupsProperty->getValue($this->backupManager);
        $checkpointsArray = $checkpointsProperty->getValue($this->backupManager);
        
        // Make them old
        $backupsArray[$backupId1]->created_at = now()->subDays(10);
        $checkpointsArray[$checkpointId1]->created_at = now()->subDays(10);
        
        $backupsProperty->setValue($this->backupManager, $backupsArray);
        $checkpointsProperty->setValue($this->backupManager, $checkpointsArray);
        
        // Act
        $cleaned = $this->backupManager->cleanup(7);
        
        // Assert
        $this->assertEquals(2, $cleaned);
        $this->assertCount(0, $this->backupManager->getBackups());
        $this->assertCount(0, $this->backupManager->getCheckpoints());
    }

    public function test_backup_record_to_array()
    {
        // Arrange
        $data = [
            'id' => 'test-backup-id',
            'commit_hash' => 'abc123',
            'description' => 'Test backup',
            'created_at' => now(),
            'session_id' => 'session-123'
        ];
        
        // Act
        $backup = new BackupRecord($data);
        $array = $backup->toArray();
        
        // Assert
        $this->assertEquals($data['id'], $array['id']);
        $this->assertEquals($data['commit_hash'], $array['commit_hash']);
        $this->assertEquals($data['description'], $array['description']);
        $this->assertEquals($data['session_id'], $array['session_id']);
        $this->assertArrayHasKey('created_at', $array);
    }

    public function test_checkpoint_record_to_array()
    {
        // Arrange
        $data = [
            'id' => 'test-checkpoint-id',
            'commit_hash' => 'def456',
            'operation' => 'Test operation',
            'metadata' => ['key' => 'value'],
            'created_at' => now(),
            'session_id' => 'session-456'
        ];
        
        // Act
        $checkpoint = new CheckpointRecord($data);
        $array = $checkpoint->toArray();
        
        // Assert
        $this->assertEquals($data['id'], $array['id']);
        $this->assertEquals($data['commit_hash'], $array['commit_hash']);
        $this->assertEquals($data['operation'], $array['operation']);
        $this->assertEquals($data['metadata'], $array['metadata']);
        $this->assertEquals($data['session_id'], $array['session_id']);
        $this->assertArrayHasKey('created_at', $array);
    }

    public function test_throws_exception_when_not_git_repository()
    {
        // Arrange - Create non-git directory
        $nonGitPath = sys_get_temp_dir() . '/non_git_' . uniqid();
        mkdir($nonGitPath);
        chdir($nonGitPath);
        
        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not a git repository');
        new GitBackupManager();
        
        // Cleanup
        rmdir($nonGitPath);
    }
}