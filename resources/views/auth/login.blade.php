<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — SPT Scheduler</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100 flex items-center justify-center px-4">

    <div class="w-full max-w-md">
        {{-- Logo / Brand --}}
        <div class="text-center mb-8">
            <h1 class="mt-4 text-2xl font-semibold text-zinc-900">SPT Scheduler</h1>
            <p class="mt-1 text-sm text-zinc-500">Masuk untuk mengakses dashboard</p>
        </div>

        {{-- Card --}}
        <div class="rounded-3xl border bg-white p-8 shadow-sm">

            {{-- Error messages --}}
            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-700">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="admin@spt.com"
                        class="mt-1 w-full rounded-2xl border px-4 py-2.5 text-sm outline-none
                               focus:ring-2 focus:ring-zinc-300 transition"
                    />
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-700">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="mt-1 w-full rounded-2xl border px-4 py-2.5 text-sm outline-none
                               focus:ring-2 focus:ring-zinc-300 transition"
                    />
                </div>

                {{-- Remember --}}
                <div class="flex items-center gap-2">
                    <input id="remember" type="checkbox" name="remember"
                           class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500" />
                    <label for="remember" class="text-sm text-zinc-600">Ingat saya</label>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full rounded-2xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white
                           hover:bg-zinc-800 active:bg-zinc-950 transition"
                >
                    Masuk
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-zinc-400">
            © {{ now()->year }} SPT Scheduler
        </p>
    </div>

</body>
</html>
