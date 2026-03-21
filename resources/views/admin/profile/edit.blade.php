@extends('layouts.admin')

@section('admin_content')
    <div class="mx-auto max-w-xl px-4 py-10 sm:px-6">
        <div class="reveal" data-reveal>
            <h1 class="font-serif text-3xl text-[#2c2418]">Profile</h1>
            <p class="mt-2 text-sm text-[#2c2418]/70">
                Update your name, email, or password for the admin panel.
            </p>
        </div>

        @if (session('success'))
            <div class="mt-6 border border-[#946112]/30 bg-[#fffdf8] px-4 py-3 text-sm text-[#2c2418] shadow-sm reveal"
                data-reveal>
                <p class="font-semibold text-[#946112]">{{ session('success') }}</p>
            </div>
        @endif

        <form action="{{ route('admin.profile.update') }}" method="POST"
            class="mt-8 border border-[#946112]/25 bg-white/80 p-6 shadow-sm sm:p-8 reveal" data-reveal>
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-[#2c2418]/70"
                        for="profile-name">Name</label>
                    <input id="profile-name" name="name" type="text" value="{{ old('name', $user->name) }}"
                        autocomplete="name"
                        class="mt-2 w-full border border-[#946112]/30 bg-white px-4 py-3 text-sm text-[#2c2418] outline-none focus:border-[#946112] focus:ring-1 focus:ring-[#946112]/35"
                        required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-[#2c2418]/70"
                        for="profile-email">Email</label>
                    <input id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}"
                        autocomplete="email"
                        class="mt-2 w-full border border-[#946112]/30 bg-white px-4 py-3 text-sm text-[#2c2418] outline-none focus:border-[#946112] focus:ring-1 focus:ring-[#946112]/35"
                        required>
                    @error('email')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-[#946112]/15 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/70">Change password</p>
                    <p class="mt-1 text-xs text-[#2c2418]/55">Leave blank to keep your current password.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#2c2418]/70" for="profile-current-password">Current
                                password</label>
                            <input id="profile-current-password" name="current_password" type="password"
                                autocomplete="current-password"
                                class="mt-2 w-full border border-[#946112]/30 bg-white px-4 py-3 text-sm text-[#2c2418] outline-none focus:border-[#946112] focus:ring-1 focus:ring-[#946112]/35">
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#2c2418]/70" for="profile-password">New
                                password</label>
                            <input id="profile-password" name="password" type="password" autocomplete="new-password"
                                class="mt-2 w-full border border-[#946112]/30 bg-white px-4 py-3 text-sm text-[#2c2418] outline-none focus:border-[#946112] focus:ring-1 focus:ring-[#946112]/35">
                            @error('password')
                                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#2c2418]/70"
                                for="profile-password-confirmation">Confirm new password</label>
                            <input id="profile-password-confirmation" name="password_confirmation" type="password"
                                autocomplete="new-password"
                                class="mt-2 w-full border border-[#946112]/30 bg-white px-4 py-3 text-sm text-[#2c2418] outline-none focus:border-[#946112] focus:ring-1 focus:ring-[#946112]/35">
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="btn-wired px-6 py-2.5 text-xs">
                        <span class="btn-wired__text">Save changes</span>
                    </button>
                    <a href="{{ route('admin.dashboard') }}"
                        class="inline-flex items-center justify-center border border-[#946112]/35 bg-white px-6 py-2.5 text-xs font-semibold text-[#2c2418] shadow-sm transition hover:border-[#946112]/55">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
@endsection
