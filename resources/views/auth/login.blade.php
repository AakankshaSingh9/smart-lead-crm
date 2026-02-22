<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Smart CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-sky-900 flex items-center justify-center px-4">
    <div class="w-full max-w-md rounded-xl bg-white shadow-xl p-8">
        <h1 class="text-2xl font-bold text-slate-800">Smart CRM Login</h1>
        <p class="text-sm text-slate-500 mt-1">Use seeded users: admin@crm.local / password</p>

        <form action="{{ route('login.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border-slate-300 focus:border-sky-500 focus:ring-sky-500" required>
                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" class="mt-1 w-full rounded-md border-slate-300 focus:border-sky-500 focus:ring-sky-500" required>
                @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300"> Remember me
            </label>

            <button type="submit" class="w-full rounded-md bg-sky-600 hover:bg-sky-700 text-white py-2 font-medium">Sign In</button>
        </form>
    </div>
</body>
</html>
