@extends('layouts.wedding')

@section('hideFooter', '1')

@section('content')
    <div class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-6">
        <div class="rounded-none border border-[#946112]/25 bg-[#fffdf8]/95 p-8 shadow-md">
            <h1 class="text-center font-serif text-2xl text-[#2c2418]">Admin sign in</h1>
            <p class="mt-2 text-center text-sm text-[#2c2418]/70">
                Sign in to manage RSVPs and view the dashboard.
            </p>

            @if ($errors->any())
                <div class="mt-6 border border-red-300/60 bg-red-50/90 px-4 py-3 text-sm text-red-900" role="alert">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-[#2c2418]/80">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                        autofocus
                        class="mt-2 w-full border border-[#946112]/30 bg-white px-4 py-3 text-sm text-[#2c2418] outline-none focus:border-[#946112] focus:ring-1 focus:ring-[#946112]/40">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-[#2c2418]/80">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        class="mt-2 w-full border border-[#946112]/30 bg-white px-4 py-3 text-sm text-[#2c2418] outline-none focus:border-[#946112] focus:ring-1 focus:ring-[#946112]/40">
                </div>

                <div class="flex items-center gap-2">
                    <input id="remember" name="remember" type="checkbox" value="1"
                        class="size-4 rounded border-[#946112]/40 text-[#946112] focus:ring-[#946112]/40"
                        @checked(old('remember'))>
                    <label for="remember" class="text-sm text-[#2c2418]/80">Remember me</label>
                </div>

                <button type="submit"
                    class="w-full border border-[#946112]/50 bg-[#946112] px-4 py-3 text-sm font-semibold text-[#fffdf8] shadow-sm transition hover:bg-[#7a5110]">
                    Sign in
                </button>
            </form>
        </div>
    </div>
@endsection
