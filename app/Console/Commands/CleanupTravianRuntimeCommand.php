<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('travian:cleanup-runtime {--dry-run : Show what would be deleted without changing data} {--batch-size= : Rows deleted per delete statement} {--max-batches= : Maximum batches per table rule in this run}')]
#[Description('Prune old local Travian runtime logs and failed jobs.')]
class CleanupTravianRuntimeCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $batchSize = max(1, (int) ($this->option('batch-size') ?: config('travian.retention.cleanup_batch_size', 500)));
        $maxBatches = max(1, (int) ($this->option('max-batches') ?: config('travian.retention.cleanup_max_batches', 20)));
        $deleted = [
            'activity_logs' => $this->pruneActivityLogs($batchSize, $maxBatches, $dryRun),
            'failed_jobs' => $this->pruneFailedJobs($batchSize, $maxBatches, $dryRun),
        ];

        foreach ($deleted as $table => $count) {
            $this->line(($dryRun ? 'Would delete ' : 'Deleted ')."{$count} {$table} row(s).");
        }

        return self::SUCCESS;
    }

    protected function pruneActivityLogs(int $batchSize, int $maxBatches, bool $dryRun): int
    {
        if (! Schema::hasTable('activity_logs')) {
            return 0;
        }

        $deletedByAge = $this->pruneTableByDate(
            DB::table('activity_logs'),
            'id',
            'created_at',
            now()->subDays(max(1, (int) config('travian.retention.activity_log_days', 7))),
            $batchSize,
            $maxBatches,
            $dryRun,
        );

        $deletedByCap = $this->pruneTableToMaxRows(
            DB::table('activity_logs'),
            'id',
            max(1, (int) config('travian.retention.activity_log_max_rows', 5000)),
            $batchSize,
            $maxBatches,
            $dryRun,
        );

        return $deletedByAge + $deletedByCap;
    }

    protected function pruneFailedJobs(int $batchSize, int $maxBatches, bool $dryRun): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        $deletedByAge = $this->pruneTableByDate(
            DB::table('failed_jobs'),
            'id',
            'failed_at',
            now()->subDays(max(1, (int) config('travian.retention.failed_job_days', 14))),
            $batchSize,
            $maxBatches,
            $dryRun,
        );

        $deletedByCap = $this->pruneTableToMaxRows(
            DB::table('failed_jobs'),
            'id',
            max(1, (int) config('travian.retention.failed_job_max_rows', 1000)),
            $batchSize,
            $maxBatches,
            $dryRun,
        );

        return $deletedByAge + $deletedByCap;
    }

    protected function pruneTableByDate(
        Builder $query,
        string $keyColumn,
        string $dateColumn,
        mixed $deleteBefore,
        int $batchSize,
        int $maxBatches,
        bool $dryRun,
    ): int {
        $deleted = 0;
        $batches = 0;

        do {
            $keys = (clone $query)
                ->where($dateColumn, '<', $deleteBefore)
                ->orderBy($keyColumn)
                ->limit($batchSize)
                ->pluck($keyColumn);

            $count = $keys->count();

            if ($count === 0) {
                break;
            }

            $batches++;

            if (! $dryRun) {
                (clone $query)->whereIn($keyColumn, $keys)->delete();
            }

            $deleted += $count;
        } while ($count === $batchSize && $batches < $maxBatches);

        return $deleted;
    }

    protected function pruneTableToMaxRows(
        Builder $query,
        string $keyColumn,
        int $maxRows,
        int $batchSize,
        int $maxBatches,
        bool $dryRun,
    ): int {
        $cutoffKey = (clone $query)
            ->orderByDesc($keyColumn)
            ->offset($maxRows)
            ->limit(1)
            ->value($keyColumn);

        if ($cutoffKey === null) {
            return 0;
        }

        $deleted = 0;
        $batches = 0;

        do {
            $keys = (clone $query)
                ->where($keyColumn, '<=', $cutoffKey)
                ->orderBy($keyColumn)
                ->limit($batchSize)
                ->pluck($keyColumn);

            $count = $keys->count();

            if ($count === 0) {
                break;
            }

            $batches++;

            if (! $dryRun) {
                (clone $query)->whereIn($keyColumn, $keys)->delete();
            }

            $deleted += $count;
        } while ($count === $batchSize && $batches < $maxBatches);

        return $deleted;
    }
}
