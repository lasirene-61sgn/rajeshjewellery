<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Craftsman Login - Jewelry ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden p-8 space-y-6">
        <div class="text-center">
            <span class="inline-block p-3 rounded-full bg-amber-100 text-amber-600 mb-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
            </span>
            <h2 class="text-2xl font-extrabold text-slate-900">Craftsman Portal</h2>
            <p class="text-xs text-slate-500 mt-1">Sign in using your registered Mobile or Email</p>
        </div>

        @if($errors->has('login'))
            <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg">
                {{ $errors->first('login') }}
            </div>
        @endif

        <form method="POST" action="{{ route('craftsman.login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Mobile Number or Email *</label>
                <input type="text" name="login" value="{{ old('login') }}" required autofocus class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Password *</label>
                <input type="password" name="password" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 mr-2">
                    Remember Me
                </label>
            </div>

            <button type="submit" class="w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm rounded-lg shadow transition-colors">
                Sign In to Workshop
            </button>
        </form>
    </div>
</body>
</html>