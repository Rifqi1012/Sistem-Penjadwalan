@extends('layouts.app')

@section('content')
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Histori Order</h1>
        <p class="text-sm text-zinc-500">
          Estimasi selesai untuk order yang belum selesai dihitung dengan simulasi kapasitas harian (forecast),
          bukan hanya dari jadwal yang sudah digenerate.
        </p>
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
            @php
              // ✅ Prioritas estimasi:
              // - jika belum selesai (pcs_remaining > 0) => gunakan forecast (simulasi kapasitas)
              // - jika sudah selesai => gunakan scheduled_finish (jadwal real)
              $finish = null;

              if ((int)$o->pcs_remaining > 0) {
                $finish = $forecast[$o->id] ?? null;
              } else {
                // sudah selesai, pakai jadwal real yang sudah terbentuk
                $finish = $o->scheduled_finish ?? null;
              }

              $st = $o->status ?? 'queued';
            @endphp

            <tr class="hover:bg-zinc-50">
              <td class="p-3">{{ $o->id }}</td>

              <td class="p-3">
                <div class="font-medium">{{ $o->customer_name }}</div>
                <div class="text-xs text-zinc-500">Sisa: {{ number_format($o->pcs_remaining) }} pcs</div>
              </td>

              <td class="p-3 text-right tabular-nums">{{ number_format($o->pcs_total) }}</td>
              <td class="p-3">{{ \Carbon\Carbon::parse($o->order_date)->format('d M Y') }}</td>
              <td class="p-3">{{ \Carbon\Carbon::parse($o->start_date)->format('d M Y') }}</td>

              {{-- Estimasi selesai --}}
              <td class="p-3">
                @if($finish)
                  <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-700">
                    {{ \Carbon\Carbon::parse($finish)->format('d M Y') }}
                  </span>
                @else
                  <span class="text-xs text-zinc-500">Belum ada estimasi</span>
                @endif
              </td>

              {{-- Status badge --}}
              <td class="p-3">
                @if($st === 'done')
                  <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">DONE</span>
                @elseif($st === 'in_progress')
                  <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">IN PROGRESS</span>
                @elseif($st === 'scheduled')
                  <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">SCHEDULED</span>
                @else
                  <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-700">QUEUED</span>
                @endif
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
