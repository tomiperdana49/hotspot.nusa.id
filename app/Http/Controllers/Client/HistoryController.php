<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Router;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Connection history: every hotspot session (RADIUS accounting) on the
 * client's routers within the retention window (see history:prune).
 * Scoped by router rather than by username so sessions of vouchers that
 * were later deleted/purged stay visible.
 */
class HistoryController extends Controller
{
    public const RETENTION_DAYS = 90;

    private const PER_PAGE = 50;

    public function index(Request $request)
    {
        [$filters, $routers] = $this->filters($request);

        $sessions = $this->query($filters, $routers)
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn ($row) => $this->present($row));

        return view('client.history.index', compact('sessions', 'filters', 'routers'));
    }

    public function export(Request $request): StreamedResponse
    {
        [$filters, $routers] = $this->filters($request);
        $query = $this->query($filters, $routers);
        $filename = "riwayat-koneksi_{$filters['from']}_{$filters['to']}.csv";

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads names correctly
            fputcsv($out, [
                __('app.history.col_start'), __('app.history.col_stop'), __('app.history.col_duration'),
                __('app.user_index.col_username'), __('app.ui.devices.device_name'), __('app.user_index.modal_mac'),
                __('app.user_index.modal_ip'), __('app.nav.router'), __('app.history.col_download'),
                __('app.history.col_upload'), __('app.history.col_reason'),
            ]);

            foreach ($query->cursor() as $row) {
                $s = $this->present($row);
                fputcsv($out, [
                    $s['start'], $s['stop'] ?? __('app.history.still_online'), $s['duration'], $s['username'],
                    $s['device_name'], $s['mac'], $s['ip'], $s['router'], $s['download'], $s['upload'], $s['reason'],
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: array{from: string, to: string, router: ?int, q: string}, 1: Collection<int, Router>}
     */
    private function filters(Request $request): array
    {
        $routers = Router::where('client_id', Auth::guard('client')->user()->client_id)
            ->whereNotNull('nas_ip')
            ->orderBy('name')
            ->get();

        $today = today();
        $earliest = $today->copy()->subDays(self::RETENTION_DAYS);

        $to = ($this->date($request->input('to')) ?? $today)->min($today)->max($earliest)->copy();
        $from = ($this->date($request->input('from')) ?? $to->copy()->subDays(6))->min($to)->max($earliest)->copy();

        return [[
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'router' => $routers->contains('id', (int) $request->input('router')) ? (int) $request->input('router') : null,
            'q' => trim((string) $request->input('q')),
        ], $routers];
    }

    private function date(mixed $value): ?Carbon
    {
        try {
            return filled($value) ? Carbon::createFromFormat('Y-m-d', (string) $value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function query(array $filters, Collection $routers): Builder
    {
        $nasIps = $filters['router']
            ? $routers->where('id', $filters['router'])->pluck('nas_ip')
            : $routers->pluck('nas_ip');

        $routerIds = $routers->pluck('id');

        return DB::table('radacct as a')
            ->leftJoin('routers as r', function ($join) use ($routerIds) {
                $join->on('r.nas_ip', '=', 'a.nasipaddress')->whereIn('r.id', $routerIds);
            })
            ->leftJoin('device_names as d', function ($join) {
                $join->on('d.router_id', '=', 'r.id')
                    ->whereRaw("d.mac_address = REPLACE(UPPER(a.callingstationid), '-', ':')");
            })
            ->whereIn('a.nasipaddress', $nasIps)
            ->whereBetween('a.acctstarttime', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']).'%';
                $macLike = str_replace(':', '-', strtoupper($like));

                $query->where(fn ($q) => $q
                    ->where('a.username', 'like', $like)
                    ->orWhere('a.framedipaddress', 'like', $like)
                    ->orWhere('a.callingstationid', 'like', $macLike)
                    ->orWhere('d.name', 'like', $like));
            })
            ->orderByDesc('a.acctstarttime')
            ->orderByDesc('a.radacctid')
            ->select([
                'a.radacctid', 'a.username', 'a.framedipaddress', 'a.callingstationid', 'a.acctstarttime',
                'a.acctstoptime', 'a.acctsessiontime', 'a.acctinputoctets', 'a.acctoutputoctets',
                'a.acctterminatecause', 'a.nasipaddress', 'r.name as router_name', 'd.name as device_name',
            ]);
    }

    private function present(object $row): array
    {
        $start = Carbon::parse($row->acctstarttime);
        $stop = $row->acctstoptime ? Carbon::parse($row->acctstoptime) : null;
        $seconds = $stop ? (int) $row->acctsessiontime : (int) $start->diffInSeconds(now());

        return [
            'id' => $row->radacctid,
            'username' => $row->username,
            'device_name' => $row->device_name ?: '-',
            'mac' => $row->callingstationid ? str_replace('-', ':', $row->callingstationid) : '-',
            'ip' => $row->framedipaddress ?: '-',
            'router' => $row->router_name ?? $row->nasipaddress,
            'start' => $start->format('d M Y H:i'),
            'stop' => $stop?->format('d M Y H:i'),
            'online' => $stop === null,
            'duration' => $this->formatDuration($seconds),
            // From the router's point of view: input = sent by the device (upload).
            'download' => $this->formatBytes((int) $row->acctoutputoctets),
            'upload' => $this->formatBytes((int) $row->acctinputoctets),
            'reason' => $this->reason($row->acctterminatecause),
        ];
    }

    private function reason(?string $cause): string
    {
        if (blank($cause)) {
            return '-';
        }

        $key = 'app.history.reasons.'.$cause;

        return __($key) === $key ? $cause : __($key);
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);

        return match (true) {
            $d > 0 => "{$d}h {$h}j",
            $h > 0 => "{$h}j {$m}m",
            default => max($m, $seconds > 0 ? 1 : 0).'m',
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
