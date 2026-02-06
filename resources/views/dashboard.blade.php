@extends('layouts.app')

@section('content')
<div class="grid gap-6 lg:grid-cols-12">

    {{-- Ringkasan --}}
    <section class="lg:col-span-12 grid gap-4 md:grid-cols-4">
        <div class="rounded-3xl bg-white border p-4">
            <div class="text-sm text-zinc-500">Unit aktif</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($activeUnits) }}</div>
            <div class="mt-1 text-xs text-zinc-400">Default 185, bisa toggle rusak</div>
        </div>
        <div class="rounded-3xl bg-white border p-4">
            <div class="text-sm text-zinc-500">Kapasitas harian</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($totalCapacity) }} pcs</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($activeUnits) }} × 240 pcs</div>
        </div>
        <div class="rounded-3xl bg-white border p-4">
            <div class="text-sm text-zinc-500">Terjadwal ({{ \Carbon\Carbon::parse($workDate)->format('d M Y') }})</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($scheduledPcs) }} pcs</div>
            <div class="mt-1 text-xs text-zinc-400">Hasil generate schedule</div>
        </div>
        <div class="rounded-3xl bg-white border p-4">
            <div class="text-sm text-zinc-500">Sisa kapasitas</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format(max(0, $totalCapacity - $scheduledPcs)) }} pcs</div>
            <div class="mt-1 text-xs text-zinc-400">Jika order kurang, unit idle</div>
        </div>
    </section>

    {{-- Form input order --}}
    <section class="lg:col-span-5 rounded-3xl border bg-white p-5">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Input Pesanan (Ditampung)</h2>
            <span class="text-xs rounded-full bg-zinc-100 px-2 py-1 text-zinc-600">1 bal = 800 pcs</span>
        </div>

        <form method="POST" action="{{ route('orders.store') }}" class="mt-4 space-y-4">
            @csrf

            <div>
                <label class="text-sm font-medium">Nama Pemesan</label>
                <input name="customer_name" value="{{ old('customer_name') }}"
                       class="mt-1 w-full rounded-2xl border px-3 py-2 outline-none focus:ring"
                       placeholder="contoh: Sheli" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-medium">Tanggal Masuk</label>
                    <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}"
                           class="mt-1 w-full rounded-2xl border px-3 py-2 outline-none focus:ring" />
                    <div class="mt-1 text-xs text-zinc-500">Diproses mulai besok otomatis</div>
                </div>
                <div>
                    <label class="text-sm font-medium">Bal (opsional)</label>
                    <input type="number" min="0" name="bales" value="{{ old('bales', 0) }}"
                           class="mt-1 w-full rounded-2xl border px-3 py-2 outline-none focus:ring"
                           placeholder="mis: 2" />
                </div>
            </div>

            <div>
                <label class="text-sm font-medium">Pcs Total (opsional)</label>
                <input type="number" min="0" name="pcs_total" value="{{ old('pcs_total', 0) }}"
                       class="mt-1 w-full rounded-2xl border px-3 py-2 outline-none focus:ring"
                       placeholder="isi kalau tidak pakai bal" />
                <div class="mt-1 text-xs text-zinc-500">
                    Jika pcs_total kosong/0, sistem pakai bal × 800.
                </div>
            </div>

            <button class="w-full rounded-2xl bg-zinc-900 px-4 py-2.5 text-white hover:opacity-95">
                Simpan Pesanan
            </button>
        </form>

        <div class="mt-6">
            <div class="text-sm font-semibold mb-2">Order Masuk Hari Ini</div>
            <div class="divide-y rounded-2xl border">
                @forelse ($todayOrders as $o)
                    <div class="p-3 flex items-center justify-between gap-3">
                        <div>
                            <div class="font-medium">{{ $o->customer_name }}</div>
                            <div class="text-xs text-zinc-500">
                                {{ number_format($o->pcs_total) }} pcs · start {{ \Carbon\Carbon::parse($o->start_date)->format('d M') }}
                            </div>
                        </div>
                        <span class="text-xs rounded-full bg-zinc-100 px-2 py-1 text-zinc-600">{{ $o->status }}</span>
                    </div>
                @empty
                    <div class="p-3 text-sm text-zinc-500">Belum ada order hari ini.</div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Generate schedule + tabel schedule --}}
    <section class="lg:col-span-7 space-y-6">

        <div class="rounded-3xl border bg-white p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Generate Jadwal (SPT)</h2>
                    <p class="text-sm text-zinc-500">Pecah order → chunk ≤ 240 pcs → SPT (chunk kecil dulu) → assign ke unit aktif.</p>
                </div>

                <form method="POST" action="{{ route('schedule.run') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="date" name="work_date" value="{{ $workDate }}"
                           class="rounded-2xl border px-3 py-2 outline-none focus:ring" />
                    <button class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-white hover:opacity-95">
                        Generate
                    </button>
                </form>
            </div>

            <div class="mt-4 flex items-center gap-2 text-sm">
                <a href="{{ route('dashboard', ['date' => now()->addDay()->toDateString()]) }}"
                   class="rounded-full bg-zinc-100 px-3 py-1 text-zinc-700 hover:bg-zinc-200">
                    Besok
                </a>
                <a href="{{ route('dashboard', ['date' => now()->toDateString()]) }}"
                   class="rounded-full bg-zinc-100 px-3 py-1 text-zinc-700 hover:bg-zinc-200">
                    Hari ini
                </a>
                <div class="ml-auto text-xs text-zinc-500">
                    Menampilkan jadwal tanggal: <span class="font-medium text-zinc-800">{{ \Carbon\Carbon::parse($workDate)->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border bg-white overflow-hidden">
            <div class="p-5 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">Jadwal Unit</h3>
                    <div class="text-xs text-zinc-500">Total row: {{ $scheduleRows->count() }}</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Unit</th>
                            <th class="px-4 py-3 text-left font-medium">Customer</th>
                            <th class="px-4 py-3 text-right font-medium">Chunk (pcs)</th>
                            <th class="px-4 py-3 text-right font-medium">Order Total</th>
                            <th class="px-4 py-3 text-left font-medium">Estimasi selesai</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($scheduleRows as $row)
                            @php
                                $est = $finishEstimates[$row->order_id] ?? null;
                            @endphp
                            <tr class="hover:bg-zinc-50">
                                <td class="px-4 py-3 font-medium">{{ $row->unit_code }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $row->customer_name }}</div>
                                    <div class="text-xs text-zinc-500">Order #{{ $row->order_id }}</div>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row->pcs) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row->pcs_total) }}</td>
                                <td class="px-4 py-3">
                                    @if ($est)
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-700">
                                            {{ \Carbon\Carbon::parse($est)->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-zinc-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-700">
                                        {{ $row->order_status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-zinc-500">
                                    Belum ada jadwal untuk tanggal ini. Klik <b>Generate</b>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Unit toggle --}}
        <div class="rounded-3xl border bg-white p-5">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold">Kelola Unit (Aktif/Rusak)</h3>
                <div class="text-xs text-zinc-500">Klik untuk toggle</div>
            </div>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 max-h-72 overflow-auto pr-1">
                @foreach ($units as $u)
                    <form method="POST" action="{{ route('units.toggle', $u) }}">
                        @csrf
                        <button type="submit"
                            class="w-full rounded-2xl border px-3 py-2 text-xs font-medium
                            {{ $u->is_active ? 'bg-white hover:bg-zinc-50' : 'bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100' }}">
                            {{ $u->code }} · {{ $u->is_active ? 'Aktif' : 'Rusak' }}
                        </button>
                    </form>
                @endforeach
            </div>

            <div class="mt-2 text-xs text-zinc-500">
                Unit rusak tidak akan dipakai saat generate jadwal.
            </div>
        </div>

    </section>

</div>
@endsection
