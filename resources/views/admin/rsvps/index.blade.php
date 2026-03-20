@extends('layouts.admin')

@section('admin_content')
    <div class="mx-auto max-w-5xl px-4 sm:px-6 py-10">
        <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
            <div class="reveal" data-reveal>
                <h1 class="font-serif text-3xl text-[#2c2418]">RSVPs</h1>
                <p class="mt-2 text-sm text-[#2c2418]/70">
                    Review submissions, approve guests, and export guest lists.
                </p>
            </div>

            <div class="flex gap-3 reveal" data-reveal>
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-[#2c2418] font-semibold border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                    Dashboard
                </a>
                <a href="{{ route('admin.rsvps.export.csv') }}"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-[#2c2418] font-semibold border border-[#946112]/50 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                    Export CSV
                </a>
            </div>
        </div>

        <div class="mt-6 bg-white/70 backdrop-blur rounded-none border border-[#946112]/20 shadow overflow-hidden reveal" data-reveal>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-[#fffdf8]/90">
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Name</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Phone</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Attendance</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Guest Count</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Date</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Status</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rsvps as $rsvp)
                            @php
                                $isApproved = (bool) ($rsvp->guest?->is_approved ?? false);
                            @endphp
                            <tr class="border-t border-[#946112]/10">
                                <td class="px-4 py-3 font-medium text-[#2c2418]">{{ $rsvp->name }}</td>
                                <td class="px-4 py-3 text-[#2c2418]">{{ $rsvp->phone }}</td>
                                <td class="px-4 py-3 text-[#2c2418] font-semibold">{{ $rsvp->attendance === 'yes' ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-3 text-[#2c2418]">{{ $rsvp->guest_count ?? '-' }}</td>
                                <td class="px-4 py-3 text-[#2c2418]/80">
                                    {{ optional($rsvp->created_at)->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($isApproved)
                                        <span class="inline-flex items-center rounded-none px-3 py-1 text-xs font-semibold bg-[#946112]/12 text-[#946112] border border-[#946112]/30">
                                            Approved
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-none px-3 py-1 text-xs font-semibold bg-white/60 text-[#2c2418]/70 border border-[#946112]/20">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($isApproved)
                                        <a href="{{ route('access-card', $rsvp->guest->id) }}" target="_blank"
                                            class="inline-flex items-center rounded-none px-4 py-2 text-xs font-semibold bg-white/70 border border-[#946112]/30 text-[#2c2418] shadow-sm hover:-translate-y-0.5 transition-all">
                                            Access Card
                                        </a>
                                    @else
                                        <form action="{{ route('admin.rsvps.approve', $rsvp) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="rounded-none px-4 py-2 text-xs font-semibold bg-[#946112] border border-[#946112]/70 text-[#fffdf8] shadow-sm hover:-translate-y-0.5 transition-all">
                                                Approve
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#946112]/10">
                                <td class="px-4 py-8 text-center text-[#2c2418]/70" colspan="7">
                                    No RSVPs yet.
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
