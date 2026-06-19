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

            <div class="flex flex-wrap gap-3 reveal" data-reveal>
                <a href="{{ route('admin.rsvps.create') }}"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-[#2c2418] font-semibold border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                    Add RSVP
                </a>
                <a href="{{ route('admin.rsvps.trashed') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-none px-5 py-2.5 text-[#2c2418] font-semibold border border-[#803b48]/30 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                    Deleted RSVPs
                </a>
                <a href="{{ route('admin.rsvps.export.csv') }}"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-[#2c2418] font-semibold border border-[#946112]/50 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                    Export CSV
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

        <form action="{{ route('admin.rsvps.index') }}" method="GET"
            class="mt-6 rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 p-4 sm:p-5 shadow">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search name or number"
                    class="w-full border border-[#946112]/30 bg-white px-3 py-2 text-sm text-[#2c2418] outline-none focus:border-[#946112]">

                <select name="attendance"
                    class="w-full border border-[#946112]/30 bg-white px-3 py-2 text-sm text-[#2c2418] outline-none focus:border-[#946112]">
                    <option value="">All attendance</option>
                    <option value="yes" @selected(($filters['attendance'] ?? '') === 'yes')>Attending (Yes)</option>
                    <option value="no" @selected(($filters['attendance'] ?? '') === 'no')>Not attending (No)</option>
                </select>

                <select name="status"
                    class="w-full border border-[#946112]/30 bg-white px-3 py-2 text-sm text-[#2c2418] outline-none focus:border-[#946112]">
                    <option value="">All status</option>
                    <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                    <option value="revoked" @selected(($filters['status'] ?? '') === 'revoked')>Revoked</option>
                </select>

                <select name="whatsapp_status"
                    class="w-full border border-[#946112]/30 bg-white px-3 py-2 text-sm text-[#2c2418] outline-none focus:border-[#946112]">
                    <option value="">All WhatsApp</option>
                    <option value="none" @selected(($filters['whatsapp_status'] ?? '') === 'none')>No WhatsApp yet</option>
                    <option value="sent" @selected(($filters['whatsapp_status'] ?? '') === 'sent')>Sent</option>
                    <option value="delivered" @selected(($filters['whatsapp_status'] ?? '') === 'delivered')>Delivered</option>
                    <option value="read" @selected(($filters['whatsapp_status'] ?? '') === 'read')>Read</option>
                    <option value="retrying" @selected(($filters['whatsapp_status'] ?? '') === 'retrying')>Retrying</option>
                    <option value="failed" @selected(($filters['whatsapp_status'] ?? '') === 'failed')>Failed</option>
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="btn-wired px-4 py-2 text-xs sm:text-sm">
                        <span class="btn-wired__text">Filter</span>
                    </button>
                    <a href="{{ route('admin.rsvps.index') }}"
                        class="inline-flex items-center justify-center rounded-none px-4 py-2 text-xs font-semibold text-[#2c2418] border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                        Clear
                    </a>
                </div>
            </div>
        </form>

        {{-- Bulk actions: separate forms + form="" attribute so row action forms are not nested inside --}}
        <form id="bulk-approve-form" action="{{ route('admin.rsvps.bulk-approve') }}" method="POST" class="hidden">
            @csrf
        </form>
        <form id="bulk-delete-form" action="{{ route('admin.rsvps.bulk-delete') }}" method="POST" class="hidden">
            @csrf
        </form>

        <div class="mt-6 space-y-4">
            <div class="flex flex-wrap gap-3">
                <button type="button" data-bulk-submit="bulk-approve-form" data-bulk-action="selected"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#2c2418] border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                    onclick="return window.submitRsvpBulkForm(this, 'Approve selected RSVPs?')">
                    Approve selected
                </button>
                <button type="submit" form="bulk-approve-form" name="action" value="all_pending"
                    class="btn-wired px-5 py-2.5 text-xs sm:text-sm"
                    @disabled(($pendingCount ?? 0) === 0)
                    onclick="return confirm('Approve all {{ $pendingCount ?? 0 }} pending RSVPs? Welcome reminders will be queued for each guest.')">
                    <span class="btn-wired__text">Approve all pending ({{ $pendingCount ?? 0 }})</span>
                </button>
                <button type="button" data-bulk-submit="bulk-delete-form"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#803b48] border border-[#803b48]/35 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                    onclick="return window.submitRsvpBulkForm(this, 'Delete selected RSVPs? Approved guests will lose access until re-added.')">
                    Delete selected
                </button>
            </div>

        <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-[#fffdf8]/90">
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">
                                <input type="checkbox" id="rsvp-select-all"
                                    class="rounded border-[#946112]/40 text-[#946112] focus:ring-[#946112]/30"
                                    aria-label="Select all RSVPs on this page">
                            </th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Name</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Phone</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Attendance</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Guest Count</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Date</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Status</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">WhatsApp</th>
                            <th class="text-left px-4 py-3 font-semibold text-[#2c2418]/80">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rsvps as $rsvp)
                            @php
                                $isApproved = (bool) ($rsvp->guest?->is_approved ?? false);
                                $canApprove = ! $isApproved && $rsvp->attendance === 'yes';
                            @endphp
                            <tr class="border-t border-[#946112]/10">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="rsvp_ids[]" value="{{ $rsvp->id }}"
                                        class="rsvp-select-checkbox rounded border-[#946112]/40 text-[#946112] focus:ring-[#946112]/30"
                                        aria-label="Select {{ $rsvp->name }}">
                                </td>
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
                                    @php
                                        $waStatus = $rsvp->guest?->whatsapp_status;
                                        $waError = $rsvp->guest?->whatsapp_error;
                                        $waPill = match ($waStatus) {
                                            'sent' => ['Sent', 'bg-white/60 text-[#2c2418]/70 border-[#946112]/30'],
                                            'delivered' => ['Delivered', 'bg-[#946112]/12 text-[#946112] border-[#946112]/40'],
                                            'read' => ['Read', 'bg-[#946112]/20 text-[#946112] border-[#946112]/50'],
                                            'retrying' => ['Retrying', 'bg-[#803b48]/10 text-[#803b48] border-[#803b48]/30'],
                                            'failed' => ['Failed', 'bg-[#803b48]/15 text-[#803b48] border-[#803b48]/40'],
                                            default => ['—', 'bg-white/50 text-[#2c2418]/40 border-[#946112]/15'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-none border px-3 py-1 text-[11px] font-semibold {{ $waPill[1] }}"
                                        @if ($waError) title="{{ $waError }}" @endif>
                                        {{ $waPill[0] }}
                                    </span>
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
                                                    <form action="{{ route('admin.rsvps.resend-whatsapp', $rsvp) }}"
                                                        method="POST" class="m-0"
                                                        onsubmit="return confirm({{ \Illuminate\Support\Js::from('Resend the WhatsApp access card to '.$rsvp->name.'?') }})">
                                                        @csrf
                                                        <button type="submit" role="menuitem"
                                                            class="w-full px-4 py-2.5 text-left text-xs font-semibold text-[#2c2418] transition-colors hover:bg-[#946112]/10">
                                                            Resend WhatsApp
                                                        </button>
                                                    </form>
                                                @elseif ($rsvp->attendance === 'yes')
                                                    <form action="{{ route('admin.rsvps.approve', $rsvp) }}" method="POST"
                                                        class="m-0">
                                                        @csrf
                                                        <button type="submit" role="menuitem"
                                                            class="btn-wired admin-action-btn w-full justify-start rounded-none border-0 px-4 py-2.5 text-xs shadow-none">
                                                            <span class="btn-wired__text">Approve</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.rsvps.mark-attending', $rsvp) }}"
                                                        method="POST" class="m-0"
                                                        onsubmit="return confirm({{ \Illuminate\Support\Js::from('Mark '.$rsvp->name.' as attending (Yes)?') }})">
                                                        @csrf
                                                        <button type="submit" role="menuitem"
                                                            class="w-full px-4 py-2.5 text-left text-xs font-semibold text-[#2c2418] transition-colors hover:bg-[#946112]/10">
                                                            Mark as attending
                                                        </button>
                                                    </form>
                                                @endif
                                                <div class="my-1 border-t border-[#946112]/15" role="separator"></div>
                                                <form action="{{ route('admin.rsvps.destroy', $rsvp) }}" method="POST"
                                                    class="m-0"
                                                    onsubmit="return confirm({{ \Illuminate\Support\Js::from('Delete RSVP for '.$rsvp->name.'? This removes them from the list and revokes any access card.') }})">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" role="menuitem"
                                                        class="w-full px-4 py-2.5 text-left text-xs font-semibold text-[#803b48] transition-colors hover:bg-[#803b48]/10">
                                                        Delete RSVP
                                                    </button>
                                                </form>
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
                                <td class="px-4 py-8 text-center text-[#2c2418]/70" colspan="9">
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
    </div>

    <script>
        window.submitRsvpBulkForm = function(buttonEl, confirmMessage) {
            var checked = document.querySelectorAll('.rsvp-select-checkbox:checked');
            if (checked.length === 0) {
                alert('Select at least one RSVP first.');
                return false;
            }

            if (!confirm(confirmMessage)) {
                return false;
            }

            var formId = buttonEl.getAttribute('data-bulk-submit');
            var form = document.getElementById(formId);
            if (!form) {
                return false;
            }

            form.querySelectorAll('input[name="rsvp_ids[]"]').forEach(function(input) {
                input.remove();
            });
            form.querySelectorAll('input[name="action"]').forEach(function(input) {
                input.remove();
            });

            checked.forEach(function(checkbox) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'rsvp_ids[]';
                input.value = checkbox.value;
                form.appendChild(input);
            });

            var bulkAction = buttonEl.getAttribute('data-bulk-action');
            if (bulkAction) {
                var actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = bulkAction;
                form.appendChild(actionInput);
            }

            form.submit();
            return false;
        };

        (function() {
            var selectAll = document.getElementById('rsvp-select-all');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.rsvp-select-checkbox').forEach(function(checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                });
            }

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

            document.querySelectorAll('details.admin-rsvp-menu').forEach(function(detailsEl) {
                detailsEl.addEventListener('toggle', function() {
                    if (detailsEl.open) {
                        document.querySelectorAll('details.admin-rsvp-menu[open]').forEach(function(other) {
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
                document.querySelectorAll('details.admin-rsvp-menu[open]').forEach(function(d) {
                    if (!d.contains(e.target)) {
                        d.removeAttribute('open');
                    }
                });
            });

            document.addEventListener('scroll', function() {
                document.querySelectorAll('details.admin-rsvp-menu[open]').forEach(function(d) {
                    d.removeAttribute('open');
                });
            }, true);
        })();
    </script>
@endsection
