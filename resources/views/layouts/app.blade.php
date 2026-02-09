<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SPT Scheduler</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900">
    <header class="sticky top-0 z-10 border-b bg-white/80 backdrop-blur">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-2xl bg-zinc-900"></div>
                <div>
                    <div class="font-semibold leading-tight">SPT Scheduler</div>
                    <div class="text-xs text-zinc-500">Laravel + Tailwind + MySQL</div>
                </div>
            </div>
            <div class="text-sm text-zinc-500 flex items-center space-x-5">
                <div>
                    {{ now()->format('d M Y, H:i') }}
                </div>
                <div>
                    <a href="/histori" class="border hover:bg-green-800 hover:text-white transition-all duration-300 px-5 py-2 rounded-full ">
                        Histori
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        @if (session('success'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                <div class="font-semibold mb-1">Ada error:</div>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mt-10 border-t bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 text-sm text-zinc-500">
            © {{ now()->year }} SPT Scheduler
        </div>
    </footer>
</body>
</html>
