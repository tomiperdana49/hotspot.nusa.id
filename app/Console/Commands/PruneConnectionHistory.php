<?php

namespace App\Console\Commands;

use App\Models\DeviceName;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneConnectionHistory extends Command
{
    protected $signature = 'history:prune {--days=90 : Keep this many days of connection history}';

    protected $description = 'Delete RADIUS accounting rows (connection history) and remembered device names older than the retention period.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        // Closed sessions that started before the cutoff. Deleted in chunks
        // so a large backlog never holds a long lock on radacct, which
        // FreeRADIUS writes to on every login/logout.
        $closed = $this->deleteInChunks(fn () => DB::table('radacct')
            ->whereNotNull('acctstoptime')
            ->where('acctstarttime', '<', $cutoff));

        // "Open" sessions the NAS never closed (router rebooted, lost
        // accounting stop) and hasn't updated since before the cutoff.
        $stale = $this->deleteInChunks(fn () => DB::table('radacct')
            ->whereNull('acctstoptime')
            ->where('acctstarttime', '<', $cutoff)
            ->where(fn ($q) => $q->whereNull('acctupdatetime')->orWhere('acctupdatetime', '<', $cutoff)));

        $names = DeviceName::where('last_seen_at', '<', $cutoff)->delete();

        $this->info("History > {$days} hari dihapus: {$closed} sesi, {$stale} sesi menggantung, {$names} nama device.");

        return self::SUCCESS;
    }

    private function deleteInChunks(callable $query): int
    {
        $total = 0;

        do {
            $deleted = $query()->limit(5000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
