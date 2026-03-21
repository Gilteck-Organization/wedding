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
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Actions</th>
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
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex justify-end">
                                        <details class="admin-rsvp-menu group relative z-20">
                                            <summary
                                                class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-none border border-[#946112]/35 bg-white/90 text-[#2c2418] shadow-sm transition-all hover:border-[#946112]/55 hover:bg-white [&::-webkit-details-marker]:hidden"
                                                aria-label="Actions for {{ $rsvp->name }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
                                                    class="opacity-80">
                                                    <circle cx="12" cy="5" r="2" />
                                                    <circle cx="12" cy="12" r="2" />
                                                    <circle cx="12" cy="19" r="2" />
                                                </svg>
                                            </summary>
                                            <div data-admin-rsvp-menu-panel role="menu"
                                                class="absolute right-0 top-full z-30 mt-1 min-w-[12.5rem] border border-[#946112]/25 bg-[#fffdf8] py-1 shadow-md">
                                                @if ($isApproved)
                                                    <a href="{{ route('access-card', $rsvp->guest) }}" target="_blank"
                                                        rel="noopener noreferrer" role="menuitem"
                                                        class="block px-4 py-2.5 text-xs font-semibold text-[#2c2418] transition-colors hover:bg-[#946112]/10">
                                                        Access card
                                                    </a>
                                                @else
                                                    <form action="{{ route('admin.rsvps.approve', $rsvp) }}" method="POST"
                                                        class="m-0">
                                                        @csrf
                                                        <button type="submit" role="menuitem"
                                                            class="btn-wired admin-action-btn w-full justify-start rounded-none border-0 px-4 py-2.5 text-xs shadow-none">
                                                            <span class="btn-wired__text">Approve</span>
                                                        </button>
                                                    </form>
                                                @endif
                                                <div class="my-1 border-t border-[#946112]/15" role="separator"></div>
                                                @if ($rsvp->attendance === 'yes')
                                                    <form action="{{ route('admin.rsvps.revoke-attendance', $rsvp) }}"
                                                        method="POST" class="m-0"
                                                        onsubmit="return confirm({{ \Illuminate\Support\Js::from('Revoke attendance for '.$rsvp->name.'? Their access card will stop working and they will show as not attending.') }})">
                                                        @csrf
                                                        <button type="submit" role="menuitem"
                                                            aria-label="Revoke attendance for {{ $rsvp->name }}"
                                                            class="w-full px-4 py-2.5 text-left text-xs font-semibold text-[#803b48] transition-colors hover:bg-[#803b48]/10">
                                                            Revoke attendance
                                                        </button>
                                                    </form>
                                                @else
                                                    <div class="px-4 py-2">
                                                        <p class="text-xs font-semibold text-[#2c2418]/45">Revoke
                                                            attendance</p>
                                                        <p class="mt-1 text-[0.65rem] leading-snug text-[#2c2418]/50">
                                                            Already not attending
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    </div>
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

    <script>
        (function() {
            function positionMenuPanel(detailsEl) {
                var panel = detailsEl.querySelector('[data-admin-rsvp-menu-panel]');
                var summaryEl = detailsEl.querySelector('summary');
                if (!panel || !summaryEl) {
                    return;
                }
                window.requestAnimationFrame(function() {
                    var r = summaryEl.getBoundingClientRect();
                    var pw = panel.offsetWidth || 200;
                    var ph = panel.offsetHeight || 120;
                    var left = r.right - pw;
                    if (left < 8) {
                        left = 8;
                    }
                    if (left + pw > window.innerWidth - 8) {
                        left = Math.max(8, window.innerWidth - pw - 8);
                    }
                    var top = r.bottom + 4;
                    if (top + ph > window.innerHeight - 8) {
                        top = Math.max(8, r.top - ph - 4);
                    }
                    panel.style.position = 'fixed';
                    panel.style.left = left + 'px';
                    panel.style.top = top + 'px';
                    panel.style.right = 'auto';
                    panel.style.marginTop = '0';
                    panel.style.zIndex = '100';
                });
            }

            function clearMenuPanelPosition(detailsEl) {
                var panel = detailsEl.querySelector('[data-admin-rsvp-menu-panel]');
                if (!panel) {
                    return;
                }
                panel.style.removeProperty('position');
                panel.style.removeProperty('left');
                panel.style.removeProperty('top');
                panel.style.removeProperty('right');
                panel.style.removeProperty('margin-top');
                panel.style.removeProperty('z-index');
            }

            document.querySelectorAll('.admin-rsvp-menu details').forEach(function(detailsEl) {
                detailsEl.addEventListener('toggle', function() {
                    if (detailsEl.open) {
                        document.querySelectorAll('.admin-rsvp-menu details[open]').forEach(function(other) {
                            if (other !== detailsEl) {
                                other.removeAttribute('open');
                            }
                        });
                        positionMenuPanel(detailsEl);
                    } else {
                        clearMenuPanelPosition(detailsEl);
                    }
                });
            });

            document.addEventListener('click', function(e) {
                document.querySelectorAll('.admin-rsvp-menu details[open]').forEach(function(d) {
                    if (!d.contains(e.target)) {
                        d.removeAttribute('open');
                    }
                });
            });

            document.addEventListener('scroll', function() {
                document.querySelectorAll('.admin-rsvp-menu details[open]').forEach(function(d) {
                    d.removeAttribute('open');
                });
            }, true);
        })();
    </script>
@endsection
