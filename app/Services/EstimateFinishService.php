<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Unit;
use Carbon\Carbon;

class EstimateFinishService
{
    /**
     * Return map [order_id => estimated_finish_date] (Y-m-d)
     * Estimasi dihitung dengan simulasi kapasitas harian.
     *
     * @param Carbon $startWorkDate tanggal mulai produksi (umumnya besok dari hari ini)
     * @param string $policy 'SPT' | 'FIFO'
     */
    public function estimateAll(Carbon $startWorkDate, string $policy = 'SPT'): array
    {
        $activeUnits = Unit::where('is_active', true)->count();
        $capacityPerDay = 240;
        $dailyCapacity = max(1, $activeUnits * $capacityPerDay);

        // Ambil order yang belum selesai
        $q = Order::query()->where('pcs_remaining', '>', 0);

        // Tentukan urutan sesuai kebijakan
        if ($policy === 'FIFO') {
            $q->orderBy('order_date')->orderBy('id');
        } else { // SPT default
            $q->orderBy('pcs_remaining')->orderBy('id');
        }

        $orders = $q->get(['id','pcs_remaining']);

        $est = [];
        $workDate = $startWorkDate->copy();
        $capLeft = $dailyCapacity;

        foreach ($orders as $o) {
            $need = (int) $o->pcs_remaining;

            while ($need > 0) {
                if ($capLeft <= 0) {
                    $workDate->addDay();
                    $capLeft = $dailyCapacity;
                }

                $take = min($need, $capLeft);
                $need -= $take;
                $capLeft -= $take;

                if ($need === 0) {
                    $est[$o->id] = $workDate->toDateString();
                    break;
                }
            }
        }

        return $est;
    }
}
