@extends('layouts.admin')

@section('admin_content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 py-10">
        <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
            <div>
                <h1 class="font-serif text-3xl text-[#2c2418]">Add RSVP</h1>
                <p class="mt-2 text-sm text-[#2c2418]/70">
                    Create a new RSVP manually from admin.
                </p>
            </div>
            <a href="{{ route('admin.rsvps.index') }}"
                class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-[#2c2418] font-semibold border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                Back to RSVPs
            </a>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-none border border-[#803b48]/30 bg-[#803b48]/10 px-4 py-3 text-sm text-[#803b48]">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.rsvps.store') }}" method="POST"
            class="mt-6 rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 p-4 sm:p-6 shadow">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-[#2c2418]/80" for="name">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                        class="mt-2 w-full border border-[#946112]/30 bg-white px-3 py-2 text-sm text-[#2c2418] outline-none focus:border-[#946112]"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#2c2418]/80" for="phone">WhatsApp number</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" data-intl-phone autocomplete="tel"
                        class="mt-2 w-full border border-[#946112]/30 bg-white px-3 py-2 text-sm text-[#2c2418] outline-none focus:border-[#946112]"
                        required>
                    <p class="mt-1 hidden text-sm text-red-700" data-phone-live-error></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#2c2418]/80" for="attendance">Attendance</label>
                    <select id="attendance" name="attendance"
                        class="mt-2 w-full border border-[#946112]/30 bg-white px-3 py-2 text-sm text-[#2c2418] outline-none focus:border-[#946112]"
                        required>
                        <option value="" disabled @selected(old('attendance') === null)>Select</option>
                        <option value="yes" @selected(old('attendance') === 'yes')>Yes</option>
                        <option value="no" @selected(old('attendance') === 'no')>No</option>
                    </select>
                </div>
                <button type="submit" class="btn-wired px-5 py-2.5 text-xs sm:text-sm">
                    <span class="btn-wired__text">Save RSVP</span>
                </button>
            </div>
        </form>
    </div>
@endsection
