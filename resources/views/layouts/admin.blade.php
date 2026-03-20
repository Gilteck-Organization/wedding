@extends('layouts.wedding')

@section('hideFooter', '1')

@section('content')
    <div class="border-b border-[#946112]/20 bg-[#fffdf8]/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <nav class="flex flex-wrap items-center gap-4 text-sm font-semibold text-[#2c2418]">
                <a href="{{ route('admin.dashboard') }}"
                    class="{{ request()->routeIs('admin.dashboard') ? 'text-[#946112]' : 'text-[#2c2418]/80 hover:text-[#946112]' }}">
                    Dashboard
                </a>
                <a href="{{ route('admin.rsvps.index') }}"
                    class="{{ request()->routeIs('admin.rsvps.*') ? 'text-[#946112]' : 'text-[#2c2418]/80 hover:text-[#946112]' }}">
                    RSVPs
                </a>
            </nav>
            <div class="flex items-center gap-3">
                <span class="text-xs text-[#2c2418]/60">{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="rounded-none border border-[#946112]/40 bg-white/80 px-4 py-2 text-xs font-semibold text-[#2c2418] shadow-sm transition hover:-translate-y-0.5">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>

    @yield('admin_content')
@endsection
