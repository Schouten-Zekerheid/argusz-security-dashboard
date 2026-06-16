<?php

namespace App\Console\Commands;

use App\Models\PipelineRun;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupRetentionData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-retention-data {--dry-run : Run the command without saving changes to the database} {--days= : Override the default retention period in days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Partially cleans up old scan results in Cosmos DB (PipelineRun findings details) older than a specified number of days (default: 30) to reduce storage costs while retaining metadata for reporting.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days = $this->option('days') !== null ? (int) $this->option('days') : config('services.retention.days', 30);
        $batchSize = config('services.retention.batch_size', 100);
        $sleepMs = config('services.retention.sleep_ms', 0);
        $cutoff = Carbon::now()->subDays($days)->toDateTimeString();

        $this->printHeader($dryRun, $days, $cutoff, $batchSize, $sleepMs);

        if ($dryRun) {
            return $this->runDryRun($cutoff, $days);
        }

        return $this->runCleanup($cutoff, $days, $batchSize, $sleepMs);
    }

    /**
     * Prints the operational configuration header to the console.
     */
    private function printHeader(bool $dryRun, int $days, string $cutoff, int $batchSize, int $sleepMs): void
    {
        $this->info('=============================================');
        $this->info('Starting Data Retention Cleanup (Cosmos DB)');
        if ($dryRun) {
            $this->info('!!! DRY RUN MODE ACTIVE - NO CHANGES WILL BE SAVED !!!');
        }
        $this->info("Retention Period: {$days} days");
        $this->info("Cutoff Date: {$cutoff}");
        $this->info("Batch Size: {$batchSize}");
        $this->info("Sleep Interval: {$sleepMs}ms");
        $this->info('=============================================');
    }

    /**
     * Run the dry-run simulation of the cleanup.
     */
    private function runDryRun(string $cutoff, int $days): int
    {
        $totalToClean = PipelineRun::where('ingested_at', '<', $cutoff)
            ->where('is_cleaned', '!=', true)
            ->count();

        $this->info("Total runs matching cleanup criteria: {$totalToClean}");

        Log::info('Data retention cleanup dry-run gestart', [
            'days' => $days,
            'cutoff' => $cutoff,
            'total_matching' => $totalToClean,
        ]);

        // Preview at most 100 runs in dry run mode
        $runs = PipelineRun::where('ingested_at', '<', $cutoff)
            ->where('is_cleaned', '!=', true)
            ->limit(100)
            ->get();

        $processedCount = 0;
        foreach ($runs as $run) {
            $findingsCount = $this->cleanFindingsInPlace($run);
            $this->info("Dry run: Run {$run->_id} (ingested at {$run->ingested_at}) - would clean {$findingsCount} findings.");
            $processedCount++;
        }

        if ($runs->isEmpty()) {
            $this->info('Geen pipeline runs gevonden die opschoning vereisen.');
        } elseif ($totalToClean > 100) {
            $this->info('Dry run limit reached (100 runs displayed).');
        }

        $this->info('=============================================');
        $this->info('Data Retention Cleanup Dry Run Completed!');
        $this->info("Total runs that would be cleaned: {$totalToClean}");
        $this->info('=============================================');

        Log::info('Data retention cleanup dry-run succesvol afgerond', [
            'total_would_be_cleaned' => $totalToClean,
        ]);

        return self::SUCCESS;
    }

    /**
     * Run the actual database cleanup using chunking.
     */
    private function runCleanup(string $cutoff, int $days, int $batchSize, int $sleepMs): int
    {
        $totalToClean = PipelineRun::where('ingested_at', '<', $cutoff)
            ->where('is_cleaned', '!=', true)
            ->count();

        $this->info("Total runs matching cleanup criteria: {$totalToClean}");

        Log::info('Data retention cleanup gestart', [
            'days' => $days,
            'cutoff' => $cutoff,
            'batch_size' => $batchSize,
            'total_matching' => $totalToClean,
        ]);

        $processedCount = 0;

        PipelineRun::where('ingested_at', '<', $cutoff)
            ->where('is_cleaned', '!=', true)
            ->chunkById($batchSize, function ($runs) use (&$processedCount, $sleepMs): void {
                foreach ($runs as $run) {
                    $this->cleanFindingsInPlace($run);
                    $run->save();
                    $processedCount++;
                }

                $this->info("Batch opgeschoond. Totaal aantal runs verwerkt: {$processedCount}");

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }, '_id');

        $this->info('=============================================');
        $this->info('Data Retention Cleanup Succesvol Voltooid!');
        $this->info("Totaal opgeschoonde runs: {$processedCount}");
        $this->info('=============================================');

        Log::info('Data retention cleanup succesvol afgerond', [
            'total_cleaned' => $processedCount,
        ]);

        return self::SUCCESS;
    }

    /**
     * Partially cleans findings details in-place on the given model.
     * Returns the count of cleaned findings.
     */
    private function cleanFindingsInPlace(PipelineRun $run): int
    {
        $runsArray = $run->runs ?? [];
        $findingsCount = 0;

        if (is_array($runsArray)) {
            foreach ($runsArray as $runIndex => $toolRun) {
                if (isset($toolRun['findings']) && is_array($toolRun['findings'])) {
                    foreach (array_keys($toolRun['findings']) as $findingIndex) {
                        $runsArray[$runIndex]['findings'][$findingIndex]['description'] = null;
                        $runsArray[$runIndex]['findings'][$findingIndex]['details'] = null;
                        $findingsCount++;
                    }
                }
            }
        }

        $run->runs = $runsArray;
        $run->is_cleaned = true;

        return $findingsCount;
    }
}
