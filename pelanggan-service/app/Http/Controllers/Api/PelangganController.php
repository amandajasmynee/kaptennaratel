<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PelangganController extends Controller
{
    public function index(Request $request)
    {
        // Pakai query() (bukan get()) dan sanitize
        $perPage = max(1, (int) $request->query('per_page', 10));
        $page    = max(1, (int) $request->query('page', 1));

        // Paginator DI-PAKSA mengikuti page dari client
        $paginator = Pelanggan::orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Map ke format custom kamu, lalu tempel balik ke paginator
        $mapped = $paginator->getCollection()
            ->map(fn ($pelanggan) => $this->formatPelangganData($pelanggan))
            ->values();

        $paginator->setCollection($mapped);

        // Kembalikan payload yang konsisten + field debug
        return response()->json([
            'status'          => 'success',
            'requested_page'  => $page,                  // ← buat verifikasi cepat di Network tab
            'per_page'        => $perPage,
            'data'            => $paginator->items(),    // ← hasil map, tetap dari paginator
            'current_page'    => $paginator->currentPage(),
            'last_page'       => $paginator->lastPage(),
            'total'           => $paginator->total(),
            'from'            => $paginator->firstItem(),
            'to'              => $paginator->lastItem(),
        ]);
    }

    public function store(Request $request)
    {
        Log::info('API Store: Menerima permintaan untuk menambah pelanggan baru.', ['data' => $request->all()]);

        $validated = $request->validate([
            'nama_pelanggan'   => 'required|string|max:100',
            'unit_id'          => 'required|integer',
            'harga_paket_id'   => 'nullable|integer',
            'alamat_pelanggan' => 'nullable|string|max:200',
            'telp_user'        => 'nullable|string|max:100',
            'rt'               => 'nullable|string|max:10',
            'rw'               => 'nullable|string|max:10',
            'kelurahan_id'     => 'nullable|string|max:150',
            'kecamatan'        => 'nullable|string|max:150',
            'id_telegram'      => 'nullable|string|max:100',
            'status_log'       => 'nullable|string|max:255',
            'status_followup'  => 'nullable|string|max:100',
            'stts_send_survei' => 'nullable|string|max:255',
            'log_aktivasi'     => 'nullable|date',
            'va_bri'           => 'nullable|string|max:150',
            'va_bca'           => 'nullable|string|max:150',
            'no_combo'         => 'nullable|string|max:100',
            'log_username_dcp' => 'nullable|string|max:200',
            'pendaftaran_id'   => 'nullable|string|max:100',
        ]);

        $unitResponse = Http::timeout(3)->get(env('UNIT_SERVICE_URL') . "/api/units/{$validated['unit_id']}");
        if ($unitResponse->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Unit tidak ditemukan'], 404);
        }

        if (!empty($validated['harga_paket_id'])) {
            $paketResponse = Http::timeout(3)->get(env('HARGA_PAKET_SERVICE_URL') . "/api/harga-paket/{$validated['harga_paket_id']}");
            if ($paketResponse->failed()) {
                return response()->json(['status' => 'error', 'message' => 'Harga paket tidak ditemukan'], 404);
            }
            $json  = $paketResponse->json();
            $paket = $json['data'] ?? $json;
            if (!$this->isPaketEnabled((array) $paket)) {
                return response()->json(['status' => 'error', 'message' => 'Harga paket tidak aktif'], 422);
            }
        }

        try {
            $last   = Pelanggan::orderByDesc('id')->first();
            $nextId = $last ? $last->id + 1 : 1;
            $kodePelanggan = 'PLG' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

            $pelanggan = new Pelanggan();
            $pelanggan->kode_pelanggan   = $kodePelanggan;
            $pelanggan->nama_pelanggan   = $validated['nama_pelanggan'];
            $pelanggan->unit_id          = $validated['unit_id'];
            $pelanggan->harga_paket_id   = $validated['harga_paket_id'] ?? null;
            $pelanggan->alamat_pelanggan = $validated['alamat_pelanggan'] ?? null;
            $pelanggan->telp_user        = $validated['telp_user'] ?? null;
            $pelanggan->rt               = $validated['rt'] ?? null;
            $pelanggan->rw               = $validated['rw'] ?? null;
            $pelanggan->kelurahan_id     = $validated['kelurahan_id'] ?? null;
            $pelanggan->kecamatan        = $validated['kecamatan'] ?? null;
            $pelanggan->id_telegram      = $validated['id_telegram'] ?? null;
            $pelanggan->status_log       = $validated['status_log'] ?? null;
            $pelanggan->status_followup  = $validated['status_followup'] ?? null;
            $pelanggan->stts_send_survei = $validated['stts_send_survei'] ?? null;
            $pelanggan->log_aktivasi     = $validated['log_aktivasi'] ?? null;
            $pelanggan->va_bri           = $validated['va_bri'] ?? null;
            $pelanggan->va_bca           = $validated['va_bca'] ?? null;
            $pelanggan->no_combo         = $validated['no_combo'] ?? null;
            $pelanggan->log_username_dcp = $validated['log_username_dcp'] ?? null;
            $pelanggan->pendaftaran_id   = $validated['pendaftaran_id'] ?? null;
            $pelanggan->save();
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan pelanggan baru: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan pelanggan baru.'], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Pelanggan berhasil ditambahkan',
            'data'    => $this->formatPelangganData($pelanggan),
        ], 201);
    }

    public function show($id)
    {
        $pelanggan = Pelanggan::find($id);
        if (!$pelanggan) {
            return response()->json(['status' => 'error', 'message' => 'Pelanggan tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatPelangganData($pelanggan),
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info('API Update: Menerima permintaan untuk memperbarui pelanggan.', ['id' => $id, 'data' => $request->all()]);

        $pelanggan = Pelanggan::find($id);
        if (!$pelanggan) {
            return response()->json(['status' => 'error', 'message' => 'Pelanggan tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'nama_pelanggan'   => 'sometimes|string|max:100',
            'unit_id'          => 'sometimes|integer',
            'harga_paket_id'   => 'nullable|integer',
            'alamat_pelanggan' => 'nullable|string|max:200',
            'telp_user'        => 'nullable|string|max:100',
            'rt'               => 'nullable|string|max:10',
            'rw'               => 'nullable|string|max:10',
            'kelurahan_id'     => 'nullable|string|max:150',
            'kecamatan'        => 'nullable|string|max:150',
            'id_telegram'      => 'nullable|string|max:100',
            'status_log'       => 'nullable|string|max:255',
            'status_followup'  => 'nullable|string|max:100',
            'stts_send_survei' => 'nullable|string|max:255',
            'log_aktivasi'     => 'nullable|date',
            'va_bri'           => 'nullable|string|max:150',
            'va_bca'           => 'nullable|string|max:150',
            'no_combo'         => 'nullable|string|max:100',
            'log_username_dcp' => 'nullable|string|max:200',
            'pendaftaran_id'   => 'nullable|string|max:100',
        ]);

        if (isset($validated['unit_id'])) {
            $unitResponse = Http::timeout(3)->get(env('UNIT_SERVICE_URL') . "/api/units/{$validated['unit_id']}");
            if ($unitResponse->failed()) {
                return response()->json(['status' => 'error', 'message' => 'Unit tidak valid'], 404);
            }
        }

        if (isset($validated['harga_paket_id'])) {
            $paketResponse = Http::timeout(3)->get(env('HARGA_PAKET_SERVICE_URL') . "/api/harga-paket/{$validated['harga_paket_id']}");
            if ($paketResponse->failed()) {
                return response()->json(['status' => 'error', 'message' => 'Harga paket tidak valid'], 404);
            }
            $json  = $paketResponse->json();
            $paket = $json['data'] ?? $json;
            if (!$this->isPaketEnabled((array) $paket)) {
                return response()->json(['status' => 'error', 'message' => 'Harga paket tidak aktif'], 422);
            }
        }

        $pelanggan->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pelanggan berhasil diperbarui',
            'data'    => $this->formatPelangganData($pelanggan),
        ]);
    }

    public function destroy($id)
    {
        $pelanggan = Pelanggan::find($id);
        if (!$pelanggan) {
            return response()->json(['status' => 'error', 'message' => 'Pelanggan tidak ditemukan'], 404);
        }

        $pelanggan->delete();

        return response()->json(['status' => 'success', 'message' => 'Pelanggan berhasil dihapus']);
    }

    // ================== Helpers ==================

    // Default pakai log_aktivasi; override via ?basis=created_at
    private function basisField(Request $r): string
    {
        $basis = strtolower((string) $r->query('basis', 'log_aktivasi'));
        return $basis === 'created_at' ? 'created_at' : 'log_aktivasi';
    }

    private function weekStartRange(int $weeks, string $tz = 'Asia/Jakarta'): array
    {
        $weeks = max(1, $weeks);
        $now   = Carbon::now($tz)->startOfWeek(); // ISO-8601 → Senin
        $arr   = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $arr[] = $now->copy()->subWeeks($i)->toDateString();
        }
        return $arr;
    }

    // ===== Helper status paket aktif/enabled (dipakai di store/update/format) =====
    private function isPaketEnabled(array $data): bool
    {
        $keys = ['enable','enabled','is_enable','is_enabled','active','is_active','aktif','status'];
        foreach ($keys as $k) {
            if (!array_key_exists($k, $data)) continue;
            $v = $data[$k];
            if (is_bool($v))    return $v;
            if (is_numeric($v)) return ((int) $v) === 1;
            if (is_string($v)) {
                $s = strtolower(trim($v));
                if (in_array($s, ['1','true','on','yes','enable','enabled','active','aktif'], true)) return true;
                if (in_array($s, ['0','false','off','no','disable','disabled','inactive','nonaktif','non-aktif'], true)) return false;
            }
        }
        // tidak ada flag → anggap tidak aktif
        return false;
    }

    // ================== METRICS & DASHBOARD ==================

    public function weeklyRegistrations(Request $r)
    {
        $weeks = (int) $r->query('weeks', 12);
        $tz    = 'Asia/Jakarta';
        $col   = $this->basisField($r);

        $labels = $this->weekStartRange($weeks, $tz);
        $start  = $labels[0];
        $end    = Carbon::parse(end($labels), $tz)->copy()->addWeek()->toDateString();

        $query = Pelanggan::query()->whereBetween($col, [
            Carbon::parse($start, $tz)->startOfDay()->utc(),
            Carbon::parse($end,   $tz)->startOfDay()->utc(),
        ]);
        if ($col !== 'created_at') $query->whereNotNull($col);

        $rows = $query->get(['id', $col])
            ->groupBy(fn($row) => Carbon::parse($row->{$col})->timezone($tz)->startOfWeek()->toDateString())
            ->map->count();

        $data = array_map(fn($w) => (int) ($rows[$w] ?? 0), $labels);

        return response()->json([
            'status' => 'success',
            'data'   => ['weeks'=>count($labels), 'labels'=>$labels, 'data'=>$data],
        ]);
    }

    public function weeklyByUnit(Request $r)
    {
        $weeks         = (int) $r->query('weeks', 12);
        $unitLimit     = max(1, (int) $r->query('unit_limit', 5));
        $includeOthers = filter_var($r->query('include_others', true), FILTER_VALIDATE_BOOL);
        $tz            = 'Asia/Jakarta';
        $col           = $this->basisField($r);

        $labels = $this->weekStartRange($weeks, $tz);
        $start  = $labels[0];
        $end    = Carbon::parse(end($labels), $tz)->copy()->addWeek()->toDateString();

        $query = Pelanggan::query()->whereBetween($col, [
            Carbon::parse($start, $tz)->startOfDay()->utc(),
            Carbon::parse($end,   $tz)->startOfDay()->utc(),
        ]);
        if ($col !== 'created_at') $query->whereNotNull($col);

        $items = $query->get(['id','unit_id',$col]);

        $totalsPerUnit = $items->groupBy('unit_id')->map->count()->sortDesc()->take($unitLimit);
        $topUnitIds    = $totalsPerUnit->keys()->all();

        $points = $items
            ->groupBy(fn($row) => Carbon::parse($row->{$col})->timezone($tz)->startOfWeek()->toDateString())
            ->map(fn($g) => $g->groupBy('unit_id')->map->count());

        $unitNames = [];
        foreach ($topUnitIds as $uid) {
            $name = 'UNIT '.$uid;
            try {
                $resp = Http::timeout(3)->get(env('UNIT_SERVICE_URL') . "/api/units/{$uid}");
                if ($resp->successful()) {
                    $json = $resp->json();
                    $data = $json['data'] ?? $json;
                    if (!empty($data['nama_unit'])) $name = $data['nama_unit'];
                }
            } catch (\Throwable $e) {
                Log::warning("weeklyByUnit: gagal ambil nama unit #{$uid}: ".$e->getMessage());
            }
            $unitNames[$uid] = $name;
        }

        $series = [];
        foreach ($topUnitIds as $uid) {
            $label = $unitNames[$uid] ?? ('UNIT '.$uid);
            $data  = [];
            foreach ($labels as $w) $data[] = (int) ($points[$w][$uid] ?? 0);
            $series[] = ['unit_id'=>$uid, 'label'=>$label, 'data'=>$data];
        }

        if ($includeOthers) {
            $others = array_fill(0, count($labels), 0);
            foreach ($labels as $i => $w) {
                if (!isset($points[$w])) continue;
                foreach ($points[$w] as $uid => $cnt) {
                    if (!in_array($uid, $topUnitIds, true)) $others[$i] += (int) $cnt;
                }
            }
            if (array_sum($others) > 0) $series[] = ['unit_id'=>null, 'label'=>'Others', 'data'=>$others];
        }

        return response()->json([
            'status' => 'success',
            'data'   => ['weeks'=>count($labels), 'labels'=>$labels, 'series'=>$series],
        ]);
    }

    public function dashboard(Request $r)
    {
        $weeks         = (int) $r->query('weeks', 12);
        $unitLimit     = max(1, (int) $r->query('unit_limit', 5));
        $includeOthers = filter_var($r->query('include_others', true), FILTER_VALIDATE_BOOL);
        $tz            = 'Asia/Jakarta';
        $col           = $this->basisField($r);

        $labels = $this->weekStartRange($weeks, $tz);
        $start  = $labels[0];
        $end    = Carbon::parse(end($labels), $tz)->copy()->addWeek()->toDateString();

        $query = Pelanggan::query()->whereBetween($col, [
            Carbon::parse($start, $tz)->startOfDay()->utc(),
            Carbon::parse($end,   $tz)->startOfDay()->utc(),
        ]);
        if ($col !== 'created_at') $query->whereNotNull($col);

        $items = $query->get(['id','unit_id',$col]);

        $weeklyMap  = $items->groupBy(fn($row) => Carbon::parse($row->{$col})->timezone($tz)->startOfWeek()->toDateString())->map->count();
        $weeklyData = array_map(fn($w) => (int) ($weeklyMap[$w] ?? 0), $labels);

        $totalsPerUnit = $items->groupBy('unit_id')->map->count()->sortDesc()->take($unitLimit);
        $topUnitIds    = $totalsPerUnit->keys()->all();

        $points = $items
            ->groupBy(fn($row) => Carbon::parse($row->{$col})->timezone($tz)->startOfWeek()->toDateString())
            ->map(fn($g) => $g->groupBy('unit_id')->map->count());

        $unitNames = [];
        foreach ($topUnitIds as $uid) {
            $name = 'UNIT '.$uid;
            try {
                $resp = Http::timeout(3)->get(env('UNIT_SERVICE_URL')."/api/units/{$uid}");
                if ($resp->successful()) {
                    $json = $resp->json();
                    $data = $json['data'] ?? $json;
                    if (!empty($data['nama_unit'])) $name = $data['nama_unit'];
                }
            } catch (\Throwable $e) {
                Log::warning("dashboard: gagal ambil nama unit #{$uid}: ".$e->getMessage());
            }
            $unitNames[$uid] = $name;
        }

        $series = [];
        foreach ($topUnitIds as $uid) {
            $label = $unitNames[$uid] ?? ('UNIT '.$uid);
            $data  = [];
            foreach ($labels as $w) $data[] = (int) ($points[$w][$uid] ?? 0);
            $series[] = ['unit_id'=>$uid, 'label'=>$label, 'data'=>$data];
        }

        if ($includeOthers) {
            $others = array_fill(0, count($labels), 0);
            foreach ($labels as $i => $w) {
                if (!isset($points[$w])) continue;
                foreach ($points[$w] as $uid => $cnt) {
                    if (!in_array($uid, $topUnitIds, true)) $others[$i] += (int) $cnt;
                }
            }
            if (array_sum($others) > 0) $series[] = ['unit_id'=>null, 'label'=>'Others', 'data'=>$others];
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'weeks'  => count($labels),
                'labels' => $labels,
                'weekly' => [ 'labels' => $labels, 'data' => $weeklyData ],
                'by_unit'=> [ 'labels' => $labels, 'series' => $series ],
            ],
        ]);
    }

    public function dashboardWeek(Request $r)
    {
        $tz            = 'Asia/Jakarta';
        $unitLimit     = max(1, (int) $r->query('unit_limit', 5));
        $includeOthers = filter_var($r->query('include_others', true), FILTER_VALIDATE_BOOL);
        $col           = $this->basisField($r);

        $weekStartParam = $r->query('week_start');
        $start = $weekStartParam ? Carbon::parse($weekStartParam, $tz) : Carbon::now($tz);
        $start = $start->startOfWeek();     // Senin
        $end   = $start->copy()->addWeek(); // exclusive

        $days = [];
        for ($i=0; $i<7; $i++) $days[] = $start->copy()->addDays($i)->toDateString();

        $query = Pelanggan::query()->whereBetween($col, [
            $start->copy()->startOfDay()->utc(),
            $end->copy()->startOfDay()->utc(),
        ]);
        if ($col !== 'created_at') $query->whereNotNull($col);

        $items = $query->get(['id','unit_id',$col]);

        $dailyMap  = $items->groupBy(fn($row) => Carbon::parse($row->{$col})->timezone($tz)->toDateString())->map->count();
        $dailyData = array_map(fn($d) => (int)($dailyMap[$d] ?? 0), $days);

        $totalsPerUnit = $items->groupBy('unit_id')->map->count()->sortDesc()->take($unitLimit);
        $topUnitIds    = $totalsPerUnit->keys()->all();

        $points = $items
            ->groupBy(fn($row) => Carbon::parse($row->{$col})->timezone($tz)->toDateString())
            ->map(fn($g) => $g->groupBy('unit_id')->map->count());

        $unitNames = [];
        foreach ($topUnitIds as $uid) {
            $name = 'UNIT '.$uid;
            try {
                $resp = Http::timeout(3)->get(env('UNIT_SERVICE_URL')."/api/units/{$uid}");
                if ($resp->successful()) {
                    $json = $resp->json();
                    $data = $json['data'] ?? $json;
                    if (!empty($data['nama_unit'])) $name = $data['nama_unit'];
                }
            } catch (\Throwable $e) {
                Log::warning("dashboardWeek: gagal ambil nama unit #{$uid}: ".$e->getMessage());
            }
            $unitNames[$uid] = $name;
        }

        $series = [];
        foreach ($topUnitIds as $uid) {
            $label = $unitNames[$uid] ?? ('UNIT '.$uid);
            $data  = [];
            foreach ($days as $d) $data[] = (int)($points[$d][$uid] ?? 0);
            $series[] = ['unit_id'=>$uid, 'label'=>$label, 'data'=>$data];
        }

        if ($includeOthers) {
            $others = array_fill(0, count($days), 0);
            foreach ($days as $i => $d) {
                if (!isset($points[$d])) continue;
                foreach ($points[$d] as $uid => $cnt) {
                    if (!in_array($uid, $topUnitIds, true)) $others[$i] += (int)$cnt;
                }
            }
            if (array_sum($others) > 0) $series[] = ['unit_id'=>null, 'label'=>'Others', 'data'=>$others];
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'week_start' => $start->toDateString(),
                'labels'     => $days,
                'daily'      => [ 'labels'=>$days, 'data'=>$dailyData ],
                'by_unit'    => [ 'labels'=>$days, 'series'=>$series ],
            ],
        ]);
    }

    private function formatPelangganData($pelanggan)
    {
        $unit  = ['id' => $pelanggan->unit_id,        'nama' => 'Data unit tidak tersedia'];
        $harga = ['id' => $pelanggan->harga_paket_id, 'keterangan' => 'Data harga paket tidak tersedia'];

        if ($pelanggan->unit_id) {
            try {
                $response = Http::timeout(3)->get(env('UNIT_SERVICE_URL') . "/api/units/{$pelanggan->unit_id}");
                if ($response->successful()) {
                    $json = $response->json();
                    $data = $json['data'] ?? $json;
                    if (isset($data['nama_unit'])) {
                        $unit = ['id' => $pelanggan->unit_id, 'nama' => $data['nama_unit']];
                    }
                }
            } catch (\Exception $e) {
                Log::error("Gagal ambil unit: " . $e->getMessage());
            }
        }

        if ($pelanggan->harga_paket_id) {
            try {
                $response = Http::timeout(3)->get(env('HARGA_PAKET_SERVICE_URL') . "/api/harga-paket/{$pelanggan->harga_paket_id}");
                if ($response->successful()) {
                    $json = $response->json();
                    $data = $json['data'] ?? $json;

                    // Hanya tampilkan detail paket kalau aktif
                    if ($this->isPaketEnabled((array) $data) && isset($data['alias_paket'])) {
                        $harga = $data;
                        $harga['id'] = $pelanggan->harga_paket_id;
                    } else {
                        // Paket non-aktif → jangan tampilkan alias/keterangan
                        $harga = ['id' => $pelanggan->harga_paket_id];
                    }
                }
            } catch (\Exception $e) {
                Log::error("Gagal ambil harga paket: " . $e->getMessage());
            }
        }

        return [
            'id'               => $pelanggan->id,
            'kode_pelanggan'   => $pelanggan->kode_pelanggan,
            'nama_pelanggan'   => $pelanggan->nama_pelanggan,
            'alamat'           => $pelanggan->alamat_pelanggan,
            'telp'             => $pelanggan->telp_user,
            'unit'             => $unit,
            'harga_paket'      => $harga,
            'rt'               => $pelanggan->rt,
            'rw'               => $pelanggan->rw,
            'kelurahan_id'     => $pelanggan->kelurahan_id,
            'kecamatan'        => $pelanggan->kecamatan,
            'id_telegram'      => $pelanggan->id_telegram,
            'status_log'       => $pelanggan->status_log,
            'status_followup'  => $pelanggan->status_followup,
            'stts_send_survei' => $pelanggan->stts_send_survei,
            'log_aktivasi'     => $pelanggan->log_aktivasi,
            'va_bri'           => $pelanggan->va_bri,
            'va_bca'           => $pelanggan->va_bca,
            'no_combo'         => $pelanggan->no_combo,
            'log_username_dcp' => $pelanggan->log_username_dcp,
            'pendaftaran_id'   => $pelanggan->pendaftaran_id,
            'created_at'       => $pelanggan->created_at,
        ];
    }

    /** ================== DASHBOARD AGGREGATE (dengan start_date & end_date) ================== */
    public function dashboardAggregate(Request $r)
    {
        $tz            = 'Asia/Jakarta';
        $group         = $this->groupParam($r);                      // day|week|month
        $periods       = max(1, (int) $r->query('periods', 12));
        $unitLimit     = max(1, (int) $r->query('unit_limit', 5));
        $includeOthers = filter_var($r->query('include_others', true), FILTER_VALIDATE_BOOL);
        $col           = $this->basisField($r);                      // log_aktivasi / created_at
        $startParam    = $r->query('start_date');                    // YYYY-MM-DD (opsional)
        $endParam      = $r->query('end_date');                      // YYYY-MM-DD (opsional)

        // 1) Normalisasi rentang berdasarkan group
        [$rangeStart, $rangeEnd] = $this->normalizeRange($group, $startParam, $endParam, $periods, $tz);

        // 2) Label bucket sesuai group
        $labels = $this->labelsFromRange($group, $rangeStart, $rangeEnd, $tz);

        // 3) Ambil data sesuai basis tanggal
        $q = Pelanggan::query()->whereBetween($col, [
            $rangeStart->copy()->startOfDay()->utc(),
            $rangeEnd->copy()->startOfDay()->utc(),
        ]);
        if ($col !== 'created_at') $q->whereNotNull($col);
        $rows = $q->get(['id','unit_id',$col]);

        // 4) bucket key per item (sesuai group)
        $bucket = function ($row) use ($tz, $col, $group) {
            $dt = Carbon::parse($row->{$col})->timezone($tz);
            return $group === 'day'   ? $dt->startOfDay()->toDateString()
                 : ($group === 'month'? $dt->startOfMonth()->toDateString()
                                       : $dt->startOfWeek()->toDateString());
        };

        // total per periode
        $totMap     = $rows->groupBy($bucket)->map->count();
        $totSeries  = array_map(fn($k) => (int)($totMap[$k] ?? 0), $labels);

        // 5) komposisi per unit (Top-N + Others)
        $topUnitIds = $rows->groupBy('unit_id')->map->count()->sortDesc()->take($unitLimit)->keys()->all();
        $points     = $rows->groupBy($bucket)->map(fn($g) => $g->groupBy('unit_id')->map->count());

        // nama unit
        $unitNames = [];
        foreach ($topUnitIds as $uid) {
            $name = 'UNIT '.$uid;
            try {
                $resp = Http::timeout(3)->get(env('UNIT_SERVICE_URL')."/api/units/{$uid}");
                if ($resp->successful()) {
                    $json = $resp->json(); $data = $json['data'] ?? $json;
                    if (!empty($data['nama_unit'])) $name = $data['nama_unit'];
                }
            } catch (\Throwable $e) {
                Log::warning("dashboardAggregate: gagal ambil nama unit #{$uid}: ".$e->getMessage());
            }
            $unitNames[$uid] = $name;
        }

        $seriesUnit = [];
        foreach ($topUnitIds as $uid) {
            $data = [];
            foreach ($labels as $l) $data[] = (int)($points[$l][$uid] ?? 0);
            $seriesUnit[] = ['unit_id'=>$uid, 'label'=>$unitNames[$uid] ?? ('UNIT '.$uid), 'data'=>$data];
        }

        if ($includeOthers) {
            $others = array_fill(0, count($labels), 0);
            foreach ($labels as $i => $l) {
                if (!isset($points[$l])) continue;
                foreach ($points[$l] as $uid => $cnt) {
                    if (!in_array($uid, $topUnitIds, true)) $others[$i] += (int)$cnt;
                }
            }
            if (array_sum($others) > 0) {
                $seriesUnit[] = ['unit_id'=>null, 'label'=>'Others', 'data'=>$others];
            }
        }

        // 6) cards (pakai anchor = endParam atau now)
        $totalAll = Pelanggan::query()->count();
        $base = Pelanggan::query(); if ($col !== 'created_at') $base->whereNotNull($col);

        $anchor = $endParam ? Carbon::parse($endParam, $tz) : Carbon::now($tz);
        $cards = [
            'total'      => $totalAll,
            'today'      => (clone $base)->whereBetween($col, [$anchor->copy()->startOfDay()->utc(),   $anchor->copy()->addDay()->startOfDay()->utc()])->count(),
            'this_week'  => (clone $base)->whereBetween($col, [$anchor->copy()->startOfWeek()->utc(),   $anchor->copy()->startOfWeek()->addWeek()->utc()])->count(),
            'this_month' => (clone $base)->whereBetween($col, [$anchor->copy()->startOfMonth()->utc(),  $anchor->copy()->startOfMonth()->addMonth()->utc()])->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'group'   => $group,
                'labels'  => $labels,
                'series'  => ['total' => $totSeries],
                'by_unit' => ['labels' => $labels, 'series' => $seriesUnit],
                'cards'   => $cards,
            ],
        ]);
    }

    // ====== helper baru ======
    private function groupParam(Request $r): string
    {
        $g = strtolower((string) $r->query('group', 'week'));
        return in_array($g, ['day','week','month'], true) ? $g : 'week';
    }

    private function periodLabels(string $group, int $periods, string $tz = 'Asia/Jakarta'): array
    {
        $periods = max(1, $periods);
        $now = Carbon::now($tz);
        $base = match ($group) {
            'day'   => $now->copy()->startOfDay(),
            'month' => $now->copy()->startOfMonth(),
            default => $now->copy()->startOfWeek(),
        };

        $labels = [];
        for ($i = $periods - 1; $i >= 0; $i--) {
            $d = $base->copy();
            if ($group === 'day')   $d->subDays($i);
            if ($group === 'week')  $d->subWeeks($i);
            if ($group === 'month') $d->subMonths($i);
            $labels[] = $d->toDateString();
        }
        return $labels;
    }

    /** ==== tambahan untuk dukung rentang tanggal jelas ==== */
    private function normalizeRange(string $group, ?string $startParam, ?string $endParam, int $periods, string $tz): array
    {
        $end = $endParam ? Carbon::parse($endParam, $tz) : Carbon::now($tz);
        $start = $startParam ? Carbon::parse($startParam, $tz) : $end->copy();

        switch ($group) {
            case 'day':
                $end   = $end->copy()->startOfDay()->addDay(); // exclusive
                $start = $startParam ? $start->copy()->startOfDay()
                                     : $end->copy()->subDays($periods)->startOfDay();
                break;
            case 'month':
                $end   = $end->copy()->startOfMonth()->addMonth();
                $start = $startParam ? $start->copy()->startOfMonth()
                                     : $end->copy()->subMonths($periods)->startOfMonth();
                break;
            default: // week
                $end   = $end->copy()->startOfWeek()->addWeek();
                $start = $startParam ? $start->copy()->startOfWeek()
                                     : $end->copy()->subWeeks($periods)->startOfWeek();
                break;
        }

        if ($start >= $end) { // guard sederhana
            $start = match ($group) {
                'day'   => $end->copy()->subDay(),
                'month' => $end->copy()->subMonth(),
                default => $end->copy()->subWeek(),
            };
        }
        return [$start, $end];
    }

    private function labelsFromRange(string $group, Carbon $start, Carbon $end, string $tz): array
    {
        $labels = [];
        $cur = $start->copy();
        while ($cur < $end) {
            $labels[] = $cur->toDateString();
            if ($group === 'day')       $cur->addDay();
            elseif ($group === 'month') $cur->addMonth();
            else                        $cur->addWeek();
        }
        return $labels;
    }
}
