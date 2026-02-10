<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EstimateFinishService;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'order_date' => ['required', 'date'],
            'bales' => ['nullable', 'integer', 'min:0'],
            'pcs_total' => ['nullable', 'integer', 'min:0'],
        ]);

        $bales = (int) ($data['bales'] ?? 0);
        $pcsFromBales = $bales > 0 ? $bales * 800 : 0;

        $pcsTotal = (int) ($data['pcs_total'] ?? 0);
        if ($pcsTotal <= 0) {
            $pcsTotal = $pcsFromBales;
        }

        if ($pcsTotal <= 0) {
            return back()->withErrors(['pcs_total' => 'Isi bales atau pcs.'])->withInput();
        }

        $orderDate = Carbon::parse($data['order_date'])->toDateString();
        $startDate = Carbon::parse($orderDate)->addDay()->toDateString();

        Order::create([
            'customer_name' => $data['customer_name'],
            'order_date' => $orderDate,
            'start_date' => $startDate,
            'bales' => $bales,
            'pcs_total' => $pcsTotal,
            'pcs_remaining' => $pcsTotal,
            'status' => 'queued',
        ]);

        return back()->with('success', 'Order berhasil ditambahkan (ditampung).');
    }

    public function history(EstimateFinishService $estimator)
    {
        $orders = Order::query()
            ->select([
                'orders.*',
                DB::raw('(SELECT MAX(work_date) FROM work_chunks WHERE work_chunks.order_id = orders.id) as scheduled_finish'),
            ])
            ->latest('orders.created_at')
            ->paginate(15)
            ->withQueryString();

        // startWorkDate: minimal mulai dari besok (atau paling awal start_date dari order yang belum selesai)
        $startWorkDate = now()->addDay();

        // Forecast untuk yang belum selesai
        $forecast = $estimator->estimateAll($startWorkDate, 'SPT');

        return view('history', compact('orders', 'forecast'));
    }
}
