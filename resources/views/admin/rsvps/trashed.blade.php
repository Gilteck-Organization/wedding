@extends('layouts.admin')

@section('admin_content')
    <div class="mx-auto max-w-5xl px-4 sm:px-6 py-10">
        <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
            <div class="reveal" data-reveal>
                <h1 class="font-serif text-3xl text-[#2c2418]">Deleted RSVPs</h1>
                <p class="mt-2 text-sm text-[#2c2418]/70">
                    Soft-deleted submissions. Restore to bring them back to the main list.
                </p>
            </div>

            <div class="flex flex-wrap gap-3 reveal" data-reveal>
                <a href="{{ route('admin.rsvps.index') }}"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-[#2c2418] font-semibold border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                    Back to RSVPs
                </a>
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-[#2c2418] font-semibold border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                    Dashboard
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-none border border-[#946112]/30 bg-[#946112]/10 px-4 py-3 text-sm text-[#2c2418]">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-none border border-[#803b48]/30 bg-[#803b48]/10 px-4 py-3 text-sm text-[#803b48]">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.rsvps.trashed') }}" method="GET"
            class="mt-6 rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 p-4 sm:p-5 shadow">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search name or number"
                    class="w-full border border-[#946112]/30 bg-white px-3 py-2 text-sm text-[#2c2418] outline-none focus:border-[#946112] sm:flex-1">

                <div class="flex gap-2">
                    <button type="submit" class="btn-wired px-4 py-2 text-xs sm:text-sm">
                        <span class="btn-wired__text">Search</span>
                    </button>
                    <a href="{{ route('admin.rsvps.trashed') }}"
                        class="inline-flex items-center justify-center rounded-none px-4 py-2 text-xs font-semibold text-[#2c2418] border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                        Clear
                    </a>
                </div>
            </div>
        </form>

        <div class="mt-6 rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-[#fffdf8]/90">
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Name</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Phone</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Attendance</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Guest Count</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Submitted</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Deleted</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rsvps as $rsvp)
                            <tr class="border-t border-[#946112]/10">
                                <td class="px-4 py-3 font-medium text-[#2c2418]">{{ $rsvp->name }}</td>
                                <td class="px-4 py-3 text-[#2c2418]">{{ $rsvp->phone }}</td>
                                <td class="px-4 py-3 text-[#2c2418] font-semibold">{{ $rsvp->attendance === 'yes' ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-3 text-[#2c2418]">{{ $rsvp->guest_count ?? '-' }}</td>
                                <td class="px-4 py-3 text-[#2c2418]/80">
                                    {{ optional($rsvp->created_at)->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3 text-[#803b48]/80">
                                    {{ optional($rsvp->deleted_at)->format('M d, Y g:i A') }}
                                </td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('admin.rsvps.restore', $rsvp) }}" method="POST" class="m-0"
                                        onsubmit="return confirm({{ \Illuminate\Support\Js::from('Restore RSVP for '.$rsvp->name.'?') }})">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-none px-4 py-2 text-xs font-semibold text-[#946112] border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                                            Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#946112]/10">
                                <td class="px-4 py-8 text-center text-[#2c2418]/70" colspan="7">
                                    No deleted RSVPs.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-4 border-t border-[#946112]/10">
                {{ $rsvps->links() }}
            </div>
        </div>
    </div>
@endsection
