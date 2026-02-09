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
     * Generate jadwal untuk tanggal produksi tertentu.
     *
     * Realistis:
     * - Generate hanya membuat chunk status "planned"
     * - Tidak mengurangi pcs_remaining (progress berkurang saat chunk DONE)
     *
     * SPT yang benar:
     * - Urutkan ORDER berdasarkan processing time terpendek (pcs_remaining terkecil)
     * - Baru split jadi chunk dan distribusikan ke unit
     */
    public function generateForDate(Carbon $workDate): void
    {
        DB::transaction(function () use ($workDate) {
            $workDateStr = $workDate->toDateString();

            // 1) Unit aktif
            $units = Unit::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            if ($units->isEmpty()) return;

            // 2) Regenerate aman: hapus planned schedule tanggal itu
            $chunkIds = WorkChunk::query()
                ->whereDate('work_date', $workDateStr)
                ->where('status', 'planned')
                ->pluck('id');

            if ($chunkIds->isNotEmpty()) {
                UnitAssignment::query()->whereIn('work_chunk_id', $chunkIds)->delete();
                WorkChunk::query()->whereIn('id', $chunkIds)->delete();
            }

            // 3) Ambil order eligible:
            // - start_date == workDate (order baru mulai hari itu)
            // - atau backlog (pcs_remaining > 0)
            // ✅ SPT: order by pcs_remaining ASC (job kecil dulu)
            $orders = Order::query()
                ->where(function ($q) use ($workDateStr) {
                    $q->whereDate('start_date', $workDateStr)
                        ->orWhere('pcs_remaining', '>', 0);
                })
                ->whereIn('status', ['queued', 'scheduled', 'in_progress'])
                ->where('pcs_remaining', '>', 0)
                ->orderBy('pcs_remaining', 'asc')   // 🔥 SPT ORDER-LEVEL
                ->orderBy('id', 'asc')              // tie-breaker stabil
                ->get();

            if ($orders->isEmpty()) return;

            // 4) Buat chunk rencana kerja (planned) dengan urutan SPT order-level
            // Kapasitas: 1 unit max 240 pcs/hari.
            // Kita alokasikan 1 chunk per unit, chunk <= 240.
            $maxChunksToday = $units->count();
            $chunkPlans = [];
            $chunkSize = 240;

            foreach ($orders as $order) {
                $remaining = (int) $order->pcs_remaining;

                while ($remaining > 0 && count($chunkPlans) < $maxChunksToday) {
                    $pcs = min($chunkSize, $remaining);
                    $chunkPlans[] = [
                        'order_id' => $order->id,
                        'pcs' => $pcs,
                    ];
                    $remaining -= $pcs;
                }

                if (count($chunkPlans) >= $maxChunksToday) {
                    break;
                }
            }

            if (empty($chunkPlans)) return;

            // 5) Simpan chunk + assign ke unit (urut unit)
            foreach ($chunkPlans as $i => $plan) {
                $chunk = WorkChunk::create([
                    'order_id' => $plan['order_id'],
                    'work_date' => $workDateStr,
                    'pcs' => $plan['pcs'],
                    'status' => 'planned',
                ]);

                UnitAssignment::create([
                    'work_chunk_id' => $chunk->id,
                    'unit_id' => $units[$i]->id,
                    'sequence' => 1,
                ]);
            }

            // 6) Set status order yang kebagian jadwal jadi scheduled (kalau sebelumnya queued)
            $scheduledOrderIds = collect($chunkPlans)->pluck('order_id')->unique()->values();

            Order::query()
                ->whereIn('id', $scheduledOrderIds)
                ->where('status', 'queued')
                ->update(['status' => 'scheduled']);
        });
    }
}
