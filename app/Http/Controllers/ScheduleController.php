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
        $workDate = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : now()->addDay()->toDateString(); // default besok

        $activeUnits = Unit::query()->where('is_active', true)->count();
        $capacityPerDay = 240;
        $totalCapacity = $activeUnits * $capacityPerDay;

        // Jadwal untuk tanggal terpilih (join biar enak tampil)
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
                'orders.status as order_status',
            ])
            ->orderBy('units.code')
            ->get();

        $scheduledPcs = (int) $scheduleRows->sum('pcs');

        // Orders masuk "hari ini" (ditampung)
        $today = now()->toDateString();
        $todayOrders = Order::query()
            ->whereDate('order_date', $today)
            ->latest()
            ->limit(10)
            ->get();

        // Estimasi selesai per order = max(work_date) dari chunk order tsb
        $finishEstimates = DB::table('work_chunks')
            ->select('order_id', DB::raw('MAX(work_date) as estimated_finish'))
            ->groupBy('order_id')
            ->pluck('estimated_finish', 'order_id'); // [order_id => date]

        // Daftar unit (buat toggle cepat) - tampilkan ringkas saja
        $units = Unit::query()->orderBy('code')->get();

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

    public function run(Request $request, SptDailyScheduler $scheduler)
    {
        $data = $request->validate([
            'work_date' => ['required', 'date'],
        ]);

        $scheduler->runForDate(Carbon::parse($data['work_date']));

        return redirect()->route('dashboard', ['date' => $data['work_date']])
            ->with('success', 'Jadwal berhasil dibuat.');
    }

    public function toggleUnit(Unit $unit)
    {
        $unit->is_active = !$unit->is_active;
        $unit->save();

        return back()->with('success', "Status {$unit->code} diubah.");
    }
}
