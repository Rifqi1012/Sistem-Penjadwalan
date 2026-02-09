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
    // Kapasitas 1 mesin per hari
    const PCS_PER_UNIT = 240;

    public function runForDate(Carbon $startDate): void
    {
        DB::transaction(function () use ($startDate) {

            /**
             * =====================================================
             * 1. HAPUS JADWAL MASA DEPAN (JANGAN SENTUH MASA LALU)
             * =====================================================
             */
            $futureChunkIds = WorkChunk::whereDate('work_date', '>', $startDate)
                ->pluck('id');

            UnitAssignment::whereIn('work_chunk_id', $futureChunkIds)->delete();
            WorkChunk::whereIn('id', $futureChunkIds)->delete();

            /**
             * =====================================================
             * 2. UPDATE STATUS ORDER BARU → SCHEDULED
             * =====================================================
             */
            Order::where('pcs_remaining', '>', 0)
                ->where('status', 'pending')
                ->update([
                    'status' => 'scheduled'
                ]);

            /**
             * =====================================================
             * 3. AMBIL UNIT AKTIF
             * =====================================================
             */
            $units = Unit::where('is_active', true)
                ->orderBy('code')
                ->get();

            if ($units->isEmpty()) {
                return;
            }

            /**
             * =====================================================
             * 4. AMBIL ORDER (SPT + FIFO)
             * =====================================================
             */
            $orders = Order::where('pcs_remaining', '>', 0)
                ->orderBy('pcs_remaining') // SPT
                ->orderBy('order_date')    // FIFO jika sama
                ->get();

            /**
             * =====================================================
             * 5. INISIALISASI HARI & KAPASITAS
             * =====================================================
             */
            $currentDate = $startDate->copy();

            $unitCapacity = [];
            foreach ($units as $unit) {
                $unitCapacity[$unit->id] = self::PCS_PER_UNIT;
            }

            /**
             * =====================================================
             * 6. PROSES PENJADWALAN PRODUKSI
             * =====================================================
             */
            foreach ($orders as $order) {

                while ($order->pcs_remaining > 0) {

                    $assigned = false;

                    foreach ($units as $unit) {

                        // Jika unit masih punya kapasitas hari ini
                        if ($unitCapacity[$unit->id] > 0) {

                            $chunkSize = min(
                                self::PCS_PER_UNIT,
                                $unitCapacity[$unit->id],
                                $order->pcs_remaining
                            );

                            // Buat chunk kerja
                            $chunk = WorkChunk::create([
                                'order_id' => $order->id,
                                'pcs' => $chunkSize,
                                'work_date' => $currentDate->toDateString(),
                            ]);

                            // Assign ke unit
                            UnitAssignment::create([
                                'work_chunk_id' => $chunk->id,
                                'unit_id' => $unit->id,
                            ]);

                            // Update kapasitas & sisa pcs
                            $unitCapacity[$unit->id] -= $chunkSize;
                            $order->pcs_remaining -= $chunkSize;

                            $assigned = true;
                            break;
                        }
                    }

                    /**
                     * Jika semua unit penuh,
                     * lanjut ke hari berikutnya
                     */
                    if (! $assigned) {
                        $currentDate->addDay();

                        foreach ($units as $unit) {
                            $unitCapacity[$unit->id] = self::PCS_PER_UNIT;
                        }
                    }
                }

                // Simpan sisa pcs order (tanpa ubah status!)
                $order->save();
            }
        });
    }
}
