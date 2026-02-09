@extends('layouts.app')

@section('content')
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Histori Order</h1>
        <p class="text-sm text-zinc-500">Menampilkan semua order + estimasi selesai berdasarkan jadwal (work_chunks).</p>
      </div>
      <a href="{{ route('dashboard') }}"
         class="rounded-2xl border bg-white px-4 py-2 text-sm hover:bg-zinc-50">
        Kembali ke Dashboard
      </a>
    </div>

    <div class="overflow-hidden rounded-2xl border bg-white">
      <table class="w-full text-sm">
        <thead class="bg-zinc-50 text-zinc-600">
          <tr>
            <th class="p-3 text-left">ID</th>
            <th class="p-3 text-left">Nama</th>
            <th class="p-3 text-right">Qty (pcs)</th>
            <th class="p-3 text-left">Tanggal Masuk</th>
            <th class="p-3 text-left">Mulai Proses</th>
            <th class="p-3 text-left">Estimasi Selesai</th>
            <th class="p-3 text-left">Status</th>
            <th class="p-3 text-left">Dibuat</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          @forelse($orders as $o)
            <tr class="hover:bg-zinc-50">
              <td class="p-3">{{ $o->id }}</td>
              <td class="p-3">
                <div class="font-medium">{{ $o->customer_name }}</div>
                <div class="text-xs text-zinc-500">Sisa: {{ number_format($o->pcs_remaining) }} pcs</div>
              </td>
              <td class="p-3 text-right tabular-nums">{{ number_format($o->pcs_total) }}</td>
              <td class="p-3">{{ \Carbon\Carbon::parse($o->order_date)->format('d M Y') }}</td>
              <td class="p-3">{{ \Carbon\Carbon::parse($o->start_date)->format('d M Y') }}</td>

              <td class="p-3">
                @if($o->estimated_finish)
                  <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-700">
                    {{ \Carbon\Carbon::parse($o->estimated_finish)->format('d M Y') }}
                  </span>
                @else
                  <span class="text-xs text-zinc-500">Belum dijadwalkan</span>
                @endif
              </td>

              <td class="p-3">
                <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-700">
                  {{ $o->status }}
                </span>
              </td>

              <td class="p-3">{{ optional($o->created_at)->format('d M Y H:i') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="p-6 text-center text-zinc-500">Belum ada data.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div>
      {{ $orders->links() }}
    </div>
  </div>
@endsection
