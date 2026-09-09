<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Portal Login</title>
    
    <!-- Tailwind CSS (via Vite or CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-slate-800 p-8 rounded-xl shadow-2xl border border-slate-700">
        
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-2xl font-bold tracking-tight text-white">
                Admin Control Center
            </h2>
            <p class="mt-2 text-sm text-slate-400">
                Single-session restricted access
            </p>
        </div>

        <!-- Flash / Custom Validation Feedback (e.g. Session Overwritten / Kickout alert) -->
        @if ($errors->has('email') && !old('email'))
            <div class="rounded-lg bg-amber-500/10 border border-amber-500/30 p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-amber-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="ml-3 text-sm text-amber-300">
                        {{ $errors->first('email') }}
                    </p>
                </div>
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('admin.login') }}" method="POST" autocomplete="off">
            @csrf

            <div class="space-y-4">
                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300">
                        Email Address
                    </label>
                    <div class="mt-1">
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            value="{{ old('email') }}" 
                            required 
                            autofocus
                            class="block w-full rounded-lg bg-slate-900 border @error('email') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-700 focus:ring-indigo-500 focus:border-indigo-500 @enderror px-3 py-2 text-white placeholder-slate-500 shadow-sm sm:text-sm focus:outline-none focus:ring-2"
                            placeholder="admin@example.com"
                        >
                    </div>
                    @error('email')
                        @if (old('email'))
                            <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @endif
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300">
                        Password
                    </label>
                    <div class="mt-1">
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            required 
                            class="block w-full rounded-lg bg-slate-900 border @error('password') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-700 focus:ring-indigo-500 focus:border-indigo-500 @enderror px-3 py-2 text-white placeholder-slate-500 shadow-sm sm:text-sm focus:outline-none focus:ring-2"
                            placeholder="••••••••••••"
                        >
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input 
                        id="remember" 
                        name="remember" 
                        type="checkbox" 
                        class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-800"
                    >
                    <label for="remember" class="ml-2 block text-sm text-slate-400">
                        Keep session active
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button 
                    type="submit" 
                    class="w-full flex justify-center py-2.5 px-4 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-800 focus:ring-indigo-500 transition-colors duration-150"
                >
                    Authenticate
                </button>
            </div>
        </form>

    </div>
</body>
</html>