<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Unit;
use App\Models\WorkChunk;
use App\Models\UnitAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SptDailyScheduler
{
    /**
     * Jalankan scheduling untuk tanggal produksi tertentu.
     * - Ambil order yang start_date == workDate atau punya pcs_remaining > 0 (backlog)
     * - Split jadi chunk <= 240
     * - SPT: urut chunk kecil dulu
     * - Assign ke unit aktif (maks 1 unit 1 chunk/hari) => total chunk max = jumlah unit aktif
     */
    public function runForDate(Carbon $workDate): void
    {
        DB::transaction(function () use ($workDate) {

            $units = Unit::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            if ($units->isEmpty()) return;

            // optional: hapus jadwal planned di tanggal itu kalau mau "regenerate"
            WorkChunk::query()->whereDate('work_date', $workDate->toDateString())->delete();

            $orders = Order::query()
                ->where(function ($q) use ($workDate) {
                    $q->whereDate('start_date', $workDate->toDateString())
                        ->orWhere('pcs_remaining', '>', 0);
                })
                ->whereIn('status', ['queued', 'scheduled', 'in_progress'])
                ->orderBy('order_date')
                ->get();

            if ($orders->isEmpty()) return;

            $chunks = collect();

            foreach ($orders as $order) {
                $remaining = (int) $order->pcs_remaining;

                // safety: kalau pcs_remaining belum diset benar
                if ($remaining <= 0) $remaining = (int) $order->pcs_total;

                while ($remaining > 0) {
                    $pcs = min(240, $remaining);
                    $chunks->push([
                        'order' => $order,
                        'pcs' => $pcs,
                    ]);
                    $remaining -= $pcs;
                }
            }

            // SPT
            $chunks = $chunks->sortBy('pcs')->values();

            // kapasitas hari itu: 1 unit = max 240 pcs, kita pakai 1 chunk per unit per hari
            $chunksForToday = $chunks->take($units->count());

            foreach ($chunksForToday as $i => $item) {
                /** @var \App\Models\Order $order */
                $order = $item['order'];
                $pcs = (int) $item['pcs'];

                $chunk = WorkChunk::create([
                    'order_id' => $order->id,
                    'work_date' => $workDate->toDateString(),
                    'pcs' => $pcs,
                    'status' => 'planned',
                ]);

                UnitAssignment::create([
                    'work_chunk_id' => $chunk->id,
                    'unit_id' => $units[$i]->id,
                    'sequence' => 1,
                ]);
            }

            // update pcs_remaining setelah “dialokasikan hari ini”
            // hitung total pcs dialokasikan per order
            $allocatedByOrder = $chunksForToday
                ->groupBy(fn($x) => $x['order']->id)
                ->map(fn($items) => $items->sum('pcs'));

            foreach ($orders as $order) {
                $allocated = (int) ($allocatedByOrder[$order->id] ?? 0);
                $newRemaining = max(0, (int)$order->pcs_remaining - $allocated);

                $order->pcs_remaining = $newRemaining;

                if ($order->status === 'queued') $order->status = 'scheduled';
                if ($newRemaining === 0) $order->status = 'done'; // kalau mau otomatis done (opsional)

                $order->save();
            }
        });
    }
}
