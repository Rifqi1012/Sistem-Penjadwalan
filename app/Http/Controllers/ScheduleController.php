<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Unit;
use App\Models\WorkChunk;
use App\Models\UnitAssignment;
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
            : now()->addDay()->toDateString();

        $activeUnits = Unit::where('is_active', true)->count();
        $totalCapacity = $activeUnits * 240;

        $scheduleRows = DB::table('work_chunks')
            ->join('unit_assignments', 'unit_assignments.work_chunk_id', '=', 'work_chunks.id')
            ->join('units', 'units.id', '=', 'unit_assignments.unit_id')
            ->join('orders', 'orders.id', '=', 'work_chunks.order_id')
            ->whereDate('work_chunks.work_date', $workDate)
            ->select([
                'work_chunks.id as chunk_id',
                'work_chunks.pcs',
                'work_chunks.work_date',
                'work_chunks.status as chunk_status',
                'units.code as unit_code',
                'orders.id as order_id',
                'orders.customer_name',
                'orders.pcs_total',
                'orders.pcs_remaining',
                'orders.status as order_status',
            ])
            ->orderBy('units.code')
            ->get();

        $scheduledPcs = (int) $scheduleRows->sum('pcs');

        $todayOrders = Order::whereDate('order_date', now()->toDateString())
            ->latest()
            ->limit(10)
            ->get();

        $finishEstimates = DB::table('work_chunks')
            ->select('order_id', DB::raw('MAX(work_date) as estimated_finish'))
            ->groupBy('order_id')
            ->pluck('estimated_finish', 'order_id');

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

    // ✅ Generate: hanya buat planned schedule
    public function run(Request $request, SptDailyScheduler $scheduler)
    {
        $data = $request->validate([
            'work_date' => ['required', 'date'],
        ]);

        $scheduler->generateForDate(Carbon::parse($data['work_date']));

        return redirect()->route('dashboard', ['date' => $data['work_date']])
            ->with('success', 'Jadwal (planned) berhasil dibuat.');
    }

    // ✅ Start Production: planned -> in_progress untuk tanggal itu
    public function startProduction(Request $request)
    {
        $data = $request->validate([
            'work_date' => ['required', 'date'],
        ]);

        $workDate = Carbon::parse($data['work_date'])->toDateString();

        DB::transaction(function () use ($workDate) {
            // semua chunk planned pada workDate jadi in_progress
            WorkChunk::query()
                ->whereDate('work_date', $workDate)
                ->where('status', 'planned')
                ->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);

            // semua order yang punya chunk in_progress -> in_progress
            $orderIds = WorkChunk::query()
                ->whereDate('work_date', $workDate)
                ->where('status', 'in_progress')
                ->pluck('order_id')
                ->unique();

            if ($orderIds->isNotEmpty()) {
                Order::query()
                    ->whereIn('id', $orderIds)
                    ->whereIn('status', ['queued', 'scheduled'])
                    ->update(['status' => 'in_progress']);
            }
        });

        return back()->with('success', 'Produksi dimulai (in_progress).');
    }

    // ✅ Mark Done per chunk: in_progress -> done dan kurangi pcs_remaining
    public function completeChunk(Request $request, WorkChunk $chunk)
    {
        DB::transaction(function () use ($chunk) {
            // guard
            if ($chunk->status === 'done') return;

            $chunk->status = 'done';
            $chunk->finished_at = now();
            if (!$chunk->started_at) $chunk->started_at = now();
            $chunk->save();

            $order = Order::lockForUpdate()->find($chunk->order_id);

            // Kurangi remaining saat benar-benar selesai
            $order->pcs_remaining = max(0, (int)$order->pcs_remaining - (int)$chunk->pcs);

            if ($order->pcs_remaining === 0) {
                $order->status = 'done';
            } else {
                $order->status = 'in_progress';
            }

            $order->save();
        });

        return back()->with('success', 'Chunk ditandai DONE dan progress order terupdate.');
    }

    public function toggleUnit(Unit $unit)
    {
        $unit->is_active = !$unit->is_active;
        $unit->save();

        return back()->with('success', "Status {$unit->code} diubah.");
    }

    public function completeDay(Request $request)
{
    $data = $request->validate([
        'work_date' => ['required', 'date'],
    ]);

    $workDate = \Carbon\Carbon::parse($data['work_date'])->toDateString();

    DB::transaction(function () use ($workDate) {

        // Ambil semua chunk yang belum done di tanggal itu
        $chunks = \App\Models\WorkChunk::query()
            ->whereDate('work_date', $workDate)
            ->whereIn('status', ['planned', 'in_progress'])
            ->lockForUpdate()
            ->get();

        if ($chunks->isEmpty()) return;

        // Tandai semua chunk jadi done
        foreach ($chunks as $chunk) {
            $chunk->status = 'done';
            $chunk->finished_at = now();
            if (!$chunk->started_at) {
                $chunk->started_at = now();
            }
            $chunk->save();
        }

        // Hitung total pcs per order
        $grouped = $chunks->groupBy('order_id');

        foreach ($grouped as $orderId => $chunkList) {
            $order = \App\Models\Order::lockForUpdate()->find($orderId);

            $totalDoneToday = $chunkList->sum('pcs');

            $order->pcs_remaining = max(
                0,
                (int)$order->pcs_remaining - (int)$totalDoneToday
            );

            if ($order->pcs_remaining === 0) {
                $order->status = 'done';
            } else {
                $order->status = 'in_progress';
            }

            $order->save();
        }
    });

    return back()->with('success', 'Semua chunk pada tanggal tersebut telah diselesaikan.');
}

}
