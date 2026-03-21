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
                <a href="{{ route('admin.access-names.index') }}"
                    class="{{ request()->routeIs('admin.access-names.*') ? 'text-[#946112]' : 'text-[#2c2418]/80 hover:text-[#946112]' }}">
                    Access names
                </a>
                <a href="{{ route('admin.profile.edit') }}"
                    class="{{ request()->routeIs('admin.profile.*') ? 'text-[#946112]' : 'text-[#2c2418]/80 hover:text-[#946112]' }}">
                    Profile
                </a>
            </nav>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.profile.edit') }}"
                    class="text-xs font-semibold text-[#2c2418]/70 hover:text-[#946112]">{{ auth()->user()->name }}</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="btn-wired btn-wired--outline px-4 py-2 text-xs transition hover:-translate-y-0.5">
                        <span class="btn-wired__text">Log out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    @yield('admin_content')
@endsection
