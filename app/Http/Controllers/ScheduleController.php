<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Unit;
use App\Models\WorkChunk;
use App\Services\SptDailyScheduler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        /**
         * =====================================================
         * TANGGAL YANG SEDANG DILIHAT (ACUAN STATUS)
         * =====================================================
         */
        $workDate = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : now()->addDay()->toDateString(); // default besok

        /**
         * =====================================================
         * INFO KAPASITAS
         * =====================================================
         */
        $activeUnits = Unit::where('is_active', true)->count();
        $capacityPerDay = 240;
        $totalCapacity = $activeUnits * $capacityPerDay;

        /**
         * =====================================================
         * JADWAL UNIT (STATUS DINAMIS, BUKAN orders.status)
         * =====================================================
         */
        $scheduleRows = DB::table('work_chunks')
            ->join('unit_assignments', 'unit_assignments.work_chunk_id', '=', 'work_chunks.id')
            ->join('units', 'units.id', '=', 'unit_assignments.unit_id')
            ->join('orders', 'orders.id', '=', 'work_chunks.order_id')
            ->whereDate('work_chunks.work_date', $workDate)
            ->select([
                'work_chunks.id as chunk_id',
                'work_chunks.pcs',
                'work_chunks.work_date',
                'units.code as unit_code',
                'units.is_active',
                'orders.id as order_id',
                'orders.customer_name',
                'orders.pcs_total',
                'orders.pcs_remaining',

                // 🔥 STATUS YANG BENAR (FIX UTAMA)
                DB::raw("
                    CASE
                        WHEN EXISTS (
                            SELECT 1
                            FROM work_chunks wc2
                            WHERE wc2.order_id = orders.id
                            AND wc2.work_date > '{$workDate}'
                        )
                        THEN 'on going'
                        ELSE 'done'
                    END as order_status
                "),

            ])
            ->orderBy('units.code')
            ->get();

        $scheduledPcs = (int) $scheduleRows->sum('pcs');

        /**
         * =====================================================
         * ORDER MASUK HARI INI (DITUMPUK / BUFFER)
         * =====================================================
         */
        $today = now()->toDateString();
        $todayOrders = Order::whereDate('order_date', $today)
            ->latest()
            ->limit(10)
            ->get();

        /**
         * =====================================================
         * ESTIMASI SELESAI PER ORDER
         * =====================================================
         */
        $finishEstimates = DB::table('work_chunks')
            ->select('order_id', DB::raw('MAX(work_date) as estimated_finish'))
            ->groupBy('order_id')
            ->pluck('estimated_finish', 'order_id');

        /**
         * =====================================================
         * DAFTAR UNIT
         * =====================================================
         */
        $units = Unit::orderBy('code')->get();

        return view('dashboard', compact(
            'workDate',
            'activeUnits',
            'totalCapacity',
            'scheduledPcs',
            'todayOrders',
            'scheduleRows',
            'finishEstimates',
            'units'
        ));
    }

    /**
     * =====================================================
     * GENERATE JADWAL (SPT)
     * =====================================================
     */
    public function run(Request $request, SptDailyScheduler $scheduler)
    {
        $data = $request->validate([
            'work_date' => ['required', 'date'],
        ]);

        $scheduler->runForDate(Carbon::parse($data['work_date']));

        return redirect()
            ->route('dashboard', ['date' => $data['work_date']])
            ->with('success', 'Jadwal berhasil dibuat.');
    }

    /**
     * =====================================================
     * TOGGLE UNIT AKTIF / NON AKTIF
     * =====================================================
     */
    public function toggleUnit(Unit $unit)
    {
        $unit->is_active = ! $unit->is_active;
        $unit->save();

        return back()->with('success', "Status {$unit->code} diubah.");
    }
}
