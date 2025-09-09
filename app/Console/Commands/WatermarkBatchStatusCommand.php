<?php

namespace App\Console\Commands;

use App\Jobs\BulkWatermarkRegeneration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WatermarkBatchStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'watermark:batch-status 
                            {batch-id? : The batch ID to check status for}
                            {--list : List all active batches}
                            {--watch : Watch batch progress in real-time}
                            {--interval=5 : Refresh interval for watch mode (seconds)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of watermark batch operations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('list')) {
            return $this->listActiveBatches();
        }

        $batchId = $this->argument('batch-id');
        
        if (!$batchId) {
            $this->error('Please provide a batch ID or use --list to see all active batches');
            return 1;
        }

        if ($this->option('watch')) {
            return $this->watchBatchProgress($batchId);
        }

        return $this->showBatchStatus($batchId);
    }

    /**
     * Show status for a specific batch
     */
    protected function showBatchStatus(string $batchId): int
    {
        $batchData = BulkWatermarkRegeneration::getBatchStatus($batchId);

        if (!$batchData) {
            $this->error("Batch not found: {$batchId}");
            return 1;
        }

        $this->info("Batch Status: {$batchId}");
        $this->line('================================');

        $this->table([
            'Property', 'Value'
        ], [
            ['Status', $this->getStatusWithIcon($batchData['status'])],
            ['Total Images', $batchData['total_images'] ?? 0],
            ['Processed', $batchData['processed'] ?? 0],
            ['Successful', $batchData['successful'] ?? 0],
            ['Failed', $batchData['failed'] ?? 0],
            ['Progress', $this->getProgressBar($batchData)],
            ['Started At', $batchData['started_at'] ?? 'N/A'],
            ['Updated At', $batchData['updated_at'] ?? 'N/A'],
        ]);

        if (isset($batchData['completed_at'])) {
            $this->line('');
            $this->info('Completion Details');
            $this->line('==================');
            $this->table([
                'Property', 'Value'
            ], [
                ['Completed At', $batchData['completed_at']],
                ['Processing Time', isset($batchData['processing_time']) ? round($batchData['processing_time'], 2) . 's' : 'N/A'],
            ]);
        }

        if (isset($batchData['failed_at'])) {
            $this->line('');
            $this->error('Failure Details');
            $this->line('===============');
            $this->table([
                'Property', 'Value'
            ], [
                ['Failed At', $batchData['failed_at']],
                ['Failure Reason', $batchData['failure_reason'] ?? 'Unknown'],
            ]);
        }

        // Show recent errors if any
        if (!empty($batchData['errors'])) {
            $this->line('');
            $this->warn('Recent Errors (' . count($batchData['errors']) . ' total)');
            $this->line('=============');
            
            $recentErrors = array_slice($batchData['errors'], -10); // Show last 10 errors
            foreach ($recentErrors as $index => $error) {
                $this->line(($index + 1) . ". {$error}");
            }
        }

        return 0;
    }

    /**
     * List all active batches
     */
    protected function listActiveBatches(): int
    {
        $this->info('Active Watermark Batches');
        $this->line('========================');

        // This is a simplified implementation
        // In a production environment, you might want to store batch IDs in a separate cache key
        $this->warn('Note: This command shows only batches that are currently cached.');
        $this->info('Use specific batch IDs to check individual batch status.');

        return 0;
    }

    /**
     * Watch batch progress in real-time
     */
    protected function watchBatchProgress(string $batchId): int
    {
        $interval = (int) $this->option('interval');
        
        $this->info("Watching batch progress: {$batchId}");
        $this->info("Refresh interval: {$interval} seconds");
        $this->info("Press Ctrl+C to stop watching");
        $this->line('');

        while (true) {
            $batchData = BulkWatermarkRegeneration::getBatchStatus($batchId);

            if (!$batchData) {
                $this->error("Batch not found: {$batchId}");
                return 1;
            }

            // Clear screen (works on most terminals)
            system('clear');
            
            $this->info("Batch Progress: {$batchId} (Updated: " . now()->format('H:i:s') . ")");
            $this->line('================================================');

            $status = $batchData['status'];
            $total = $batchData['total_images'] ?? 0;
            $processed = $batchData['processed'] ?? 0;
            $successful = $batchData['successful'] ?? 0;
            $failed = $batchData['failed'] ?? 0;
            $progress = $batchData['progress_percentage'] ?? 0;

            $this->line("Status: {$this->getStatusWithIcon($status)}");
            $this->line("Progress: {$progress}% ({$processed}/{$total})");
            $this->line("Successful: {$successful}");
            $this->line("Failed: {$failed}");
            $this->line('');

            // Show progress bar
            $this->showProgressBar($progress);

            // Check if batch is complete
            if (in_array($status, ['completed', 'failed'])) {
                $this->line('');
                if ($status === 'completed') {
                    $this->info('✅ Batch completed successfully!');
                } else {
                    $this->error('❌ Batch failed!');
                    if (isset($batchData['failure_reason'])) {
                        $this->line("Reason: {$batchData['failure_reason']}");
                    }
                }
                break;
            }

            sleep($interval);
        }

        return 0;
    }

    /**
     * Get status with appropriate icon
     */
    protected function getStatusWithIcon(string $status): string
    {
        return match ($status) {
            'processing' => '🔄 Processing',
            'completed' => '✅ Completed',
            'failed' => '❌ Failed',
            default => "❓ {$status}"
        };
    }

    /**
     * Get progress bar representation
     */
    protected function getProgressBar(array $batchData): string
    {
        $progress = $batchData['progress_percentage'] ?? 0;
        $total = $batchData['total_images'] ?? 0;
        $processed = $batchData['processed'] ?? 0;

        return "{$progress}% ({$processed}/{$total})";
    }

    /**
     * Show visual progress bar
     */
    protected function showProgressBar(float $progress): void
    {
        $barLength = 50;
        $filledLength = (int) (($progress / 100) * $barLength);
        $emptyLength = $barLength - $filledLength;

        $bar = str_repeat('█', $filledLength) . str_repeat('░', $emptyLength);
        $this->line("[{$bar}] {$progress}%");
    }
}
