@extends('layouts.admin')

@section('admin_content')
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <div class="reveal" data-reveal>
            <h1 class="font-serif text-3xl text-[#2c2418]">Dashboard</h1>
            <p class="mt-2 text-sm text-[#2c2418]/70">
                Overview of RSVP responses and venue capacity.
            </p>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-none border border-[#946112]/25 bg-white/80 p-5 shadow-sm reveal" data-reveal>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60">Venue capacity</p>
                <p class="mt-2 font-serif text-3xl text-[#946112]">{{ number_format($capacity) }}</p>
                <p class="mt-1 text-xs text-[#2c2418]/65">Total seats reserved for the celebration.</p>
            </div>

            <div class="rounded-none border border-[#946112]/25 bg-white/80 p-5 shadow-sm reveal" data-reveal>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60">Seats reserved</p>
                <p class="mt-2 font-serif text-3xl text-[#2c2418]">{{ number_format($seatsReserved) }}</p>
                <p class="mt-1 text-xs text-[#2c2418]/65">From &ldquo;yes&rdquo; RSVPs (party sizes summed).</p>
            </div>

            <div class="rounded-none border border-[#946112]/25 bg-white/80 p-5 shadow-sm reveal" data-reveal>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60">Remaining slots</p>
                <p class="mt-2 font-serif text-3xl {{ $remainingSlots === 0 ? 'text-red-800' : 'text-[#5c4a33]' }}">
                    {{ number_format($remainingSlots) }}
                </p>
                <p class="mt-1 text-xs text-[#2c2418]/65">Capacity minus seats reserved.</p>
            </div>

            <div class="rounded-none border border-[#946112]/25 bg-white/80 p-5 shadow-sm reveal" data-reveal>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60">Total RSVPs</p>
                <p class="mt-2 font-serif text-3xl text-[#2c2418]">{{ number_format($totalRsvps) }}</p>
                <p class="mt-1 text-xs text-[#2c2418]/65">All submissions received.</p>
            </div>

            <div class="rounded-none border border-[#946112]/25 bg-white/80 p-5 shadow-sm reveal" data-reveal>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60">Attending / not attending</p>
                <p class="mt-2 font-serif text-2xl text-[#2c2418]">
                    <span class="text-[#946112]">{{ number_format($attendanceYes) }}</span>
                    <span class="text-[#2c2418]/40">/</span>
                    <span>{{ number_format($attendanceNo) }}</span>
                </p>
                <p class="mt-1 text-xs text-[#2c2418]/65">Yes vs. no responses.</p>
            </div>

            <div class="rounded-none border border-[#946112]/25 bg-white/80 p-5 shadow-sm reveal" data-reveal>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60">Pending approval</p>
                <p class="mt-2 font-serif text-3xl text-[#b8924a]">{{ number_format($pendingRsvps) }}</p>
                <p class="mt-1 text-xs text-[#2c2418]/65">RSVPs awaiting guest approval.</p>
            </div>

            <div class="rounded-none border border-[#946112]/25 bg-white/80 p-5 shadow-sm reveal" data-reveal>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60">Approved guests</p>
                <p class="mt-2 font-serif text-3xl text-[#2c2418]">{{ number_format($approvedGuests) }}</p>
                <p class="mt-1 text-xs text-[#2c2418]/65">Guest records with approved access cards.</p>
            </div>
        </div>

        <div class="mt-10 reveal" data-reveal>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="font-serif text-xl text-[#2c2418]">Recent RSVPs</h2>
                <a href="{{ route('admin.rsvps.index') }}"
                    class="text-sm font-semibold text-[#946112] hover:underline">View all</a>
            </div>
            <div class="mt-4 overflow-hidden rounded-none border border-[#946112]/20 bg-white/80 shadow-sm">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-[#fffdf8]/90">
                            <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Attendance</th>
                            <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Guests</th>
                            <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentRsvps as $rsvp)
                            <tr class="border-t border-[#946112]/10">
                                <td class="px-4 py-3 font-medium text-[#2c2418]">{{ $rsvp->name }}</td>
                                <td class="px-4 py-3 text-[#2c2418]">{{ $rsvp->attendance === 'yes' ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-3 text-[#2c2418]">{{ $rsvp->guest_count ?? '—' }}</td>
                                <td class="px-4 py-3 text-[#2c2418]/75">
                                    {{ optional($rsvp->created_at)->format('M j, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#946112]/10">
                                <td class="px-4 py-8 text-center text-[#2c2418]/65" colspan="4">No RSVPs yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
