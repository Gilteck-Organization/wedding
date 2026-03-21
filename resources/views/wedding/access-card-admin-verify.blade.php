@extends('layouts.wedding')

@section('hideFooter', '1')

@section('content')
    <div class="flex min-h-svh items-start justify-center px-4 py-10 sm:px-6 sm:py-14">
        <div class="w-full max-w-lg">
            <div class="border border-[#946112]/30 bg-[#fffdf8] p-6 shadow-md sm:p-8">
                <p class="text-center text-xs font-semibold uppercase tracking-widest text-[#946112]">Staff verification
                </p>
                <h1 class="mt-3 text-center font-serif text-2xl text-[#2c2418]">Attendance verified</h1>
                <p class="mt-2 text-center text-sm text-[#2c2418]/65">
                    This invite is valid for check-in. Details below match the guest record.
                </p>

                <div class="mt-8 space-y-4 border-t border-[#946112]/15 pt-6 text-[#2c2418]">
                    <div class="flex justify-between gap-4 text-sm">
                        <span class="text-[#2c2418]/60">Guest</span>
                        <span class="font-semibold text-right">{{ $guest->name }}</span>
                    </div>
                    @php
                        $rsvp = $guest->latestRsvp;
                        $attending = $rsvp?->attendance === 'yes';
                        $party = $rsvp?->guest_count;
                    @endphp
                    <div class="flex justify-between gap-4 text-sm">
                        <span class="text-[#2c2418]/60">RSVP attendance</span>
                        <span class="font-semibold">{{ $attending ? 'Attending' : 'Not attending' }}</span>
                    </div>
                    @if ($attending && $party !== null)
                        <div class="flex justify-between gap-4 text-sm">
                            <span class="text-[#2c2418]/60">Party size</span>
                            <span class="font-semibold">{{ $party }} {{ $party === 1 ? 'guest' : 'guests' }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4 text-sm">
                        <span class="text-[#2c2418]/60">Approval</span>
                        <span class="font-semibold text-[#946112]">{{ $guest->is_approved ? 'Approved' : 'Pending' }}</span>
                    </div>
                </div>

                <div
                    class="mt-8 flex items-center justify-center gap-2 rounded-sm border border-[#2d6a4f]/40 bg-[#2d6a4f]/10 px-4 py-3 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 shrink-0 text-[#2d6a4f]" viewBox="0 0 24 24"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-semibold text-[#1b4332]">OK to admit — QR matches this guest</span>
                </div>
            </div>
        </div>
    </div>
@endsection
