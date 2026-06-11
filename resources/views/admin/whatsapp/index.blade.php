@extends('layouts.admin')

@section('admin_content')
    <div class="mx-auto max-w-6xl px-4 sm:px-6 py-10">
        <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
            <div>
                <h1 class="font-serif text-3xl text-[#2c2418]">WhatsApp delivery</h1>
                <p class="mt-2 text-sm text-[#2c2418]/70">
                    Guests receive a greeting reminder first, then their access card a moment later.
                </p>
            </div>
            <a href="{{ route('admin.rsvps.index') }}"
                class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-[#2c2418] font-semibold border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all">
                RSVPs
            </a>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-none border border-[#946112]/30 bg-[#946112]/10 px-4 py-3 text-sm text-[#2c2418]">
                {{ session('success') }}
            </div>
        @endif

        @unless ($whatsappConfigured)
            <div class="mt-6 rounded-none border border-[#803b48]/30 bg-[#803b48]/10 px-4 py-3 text-sm text-[#803b48]">
                WhatsApp is not fully configured. Set <code class="text-xs">WHATSAPP_ACCESS_TOKEN</code>,
                <code class="text-xs">WHATSAPP_REMINDER_TEMPLATE_NAME</code>, and
                <code class="text-xs">WHATSAPP_TEMPLATE_NAME</code> in your environment.
            </div>
        @endunless

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Approved</p>
                <p class="mt-1 font-serif text-2xl text-[#2c2418]">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">No reminder yet</p>
                <p class="mt-1 font-serif text-2xl text-[#2c2418]">{{ $stats['never_sent'] }}</p>
            </div>
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Reminder only</p>
                <p class="mt-1 font-serif text-2xl text-[#2c2418]">{{ $stats['reminder_only'] }}</p>
            </div>
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Sent / queued</p>
                <p class="mt-1 font-serif text-2xl text-[#2c2418]">{{ $stats['sent'] }}</p>
            </div>
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Delivered / read</p>
                <p class="mt-1 font-serif text-2xl text-[#946112]">{{ $stats['delivered'] }}</p>
            </div>
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Failed</p>
                <p class="mt-1 font-serif text-2xl text-[#803b48]">{{ $stats['failed'] }}</p>
            </div>
        </div>

        <form action="{{ route('admin.whatsapp.send') }}" method="POST" class="mt-6 space-y-4">
            @csrf

            <div class="flex flex-wrap gap-3">
                <button type="submit" name="action" value="all_pending"
                    class="btn-wired px-5 py-2.5 text-xs sm:text-sm"
                    @disabled(! $whatsappConfigured)
                    onclick="return confirm('Send to all approved guests who were never sent or failed?')">
                    <span class="btn-wired__text">Send pending &amp; failed</span>
                </button>
                <button type="submit" name="action" value="all_failed"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#803b48] border border-[#803b48]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                    @disabled(! $whatsappConfigured)
                    onclick="return confirm('Resend to all failed guests?')">
                    Resend all failed
                </button>
                <button type="submit" name="action" value="all_approved"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#2c2418] border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                    @disabled(! $whatsappConfigured)
                    onclick="return confirm('Force resend to every approved guest? Use only if you need to refresh all cards.')">
                    Resend all approved
                </button>
                <button type="submit" name="action" value="selected"
                    class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#2c2418] border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                    @disabled(! $whatsappConfigured)
                    onclick="return confirm('Send to selected guests?')">
                    Send selected
                </button>
            </div>

            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-[#fffdf8]/90">
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" id="select-all-whatsapp"
                                        class="rounded border-[#946112]/40 text-[#946112] focus:ring-[#946112]/30">
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Guest</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Phone</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Reminder</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Access card</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Last sent</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Attempts</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Why / error</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Card</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($guests as $guest)
                                @php
                                    $waStatus = $guest->whatsapp_status;
                                    $waPill = match ($waStatus) {
                                        'sent' => ['Sent', 'bg-white/60 text-[#2c2418]/70 border-[#946112]/30'],
                                        'delivered' => ['Delivered', 'bg-[#946112]/12 text-[#946112] border-[#946112]/40'],
                                        'read' => ['Read', 'bg-[#946112]/20 text-[#946112] border-[#946112]/50'],
                                        'retrying' => ['Retrying', 'bg-[#803b48]/10 text-[#803b48] border-[#803b48]/30'],
                                        'failed' => ['Failed', 'bg-[#803b48]/15 text-[#803b48] border-[#803b48]/40'],
                                        default => ['Not sent', 'bg-white/50 text-[#2c2418]/40 border-[#946112]/15'],
                                    };
                                    $reminderLabel = filled($guest->whatsapp_reminder_error)
                                        ? ['Failed', 'bg-[#803b48]/15 text-[#803b48] border-[#803b48]/40']
                                        : ($guest->whatsapp_reminder_sent_at
                                            ? ['Sent', 'bg-[#946112]/12 text-[#946112] border-[#946112]/40']
                                            : ['Not sent', 'bg-white/50 text-[#2c2418]/40 border-[#946112]/15']);
                                    $reason = $guest->whatsapp_reminder_error ?: $guest->whatsapp_error;
                                    if ($reason === null && $guest->whatsapp_reminder_sent_at === null && $waStatus === null) {
                                        $reason = 'Not queued yet';
                                    } elseif ($reason === null && in_array($waStatus, ['sent', 'delivered', 'read'], true)) {
                                        $reason = '—';
                                    } elseif ($reason === null && $guest->whatsapp_reminder_sent_at !== null && $waStatus === null) {
                                        $reason = 'Waiting for access card send';
                                    }
                                @endphp
                                <tr class="border-t border-[#946112]/10">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="guest_ids[]" value="{{ $guest->id }}"
                                            class="whatsapp-guest-checkbox rounded border-[#946112]/40 text-[#946112] focus:ring-[#946112]/30">
                                    </td>
                                    <td class="px-4 py-3 font-medium text-[#2c2418]">{{ $guest->name }}</td>
                                    <td class="px-4 py-3 text-[#2c2418]">{{ $guest->phone }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-none border px-3 py-1 text-[11px] font-semibold {{ $reminderLabel[1] }}">
                                            {{ $reminderLabel[0] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-none border px-3 py-1 text-[11px] font-semibold {{ $waPill[1] }}">
                                            {{ $waPill[0] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-[#2c2418]/80">
                                        {{ optional($guest->whatsapp_last_sent_at)->format('M d, Y g:i A') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-[#2c2418]/80">{{ $guest->whatsapp_attempts }}</td>
                                    <td class="px-4 py-3 text-xs text-[#803b48] max-w-xs break-words">
                                        {{ $reason }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            <a href="{{ route('access-card', $guest) }}" target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-xs font-semibold text-[#946112] hover:underline">
                                                Web card
                                            </a>
                                            <a href="{{ route('access-card.image', $guest) }}" target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-xs font-semibold text-[#946112] hover:underline">
                                                JPEG preview
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-[#946112]/10">
                                    <td class="px-4 py-8 text-center text-[#2c2418]/70" colspan="9">
                                        No approved guests yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>

    <script>
        (function() {
            var selectAll = document.getElementById('select-all-whatsapp');
            if (!selectAll) {
                return;
            }
            selectAll.addEventListener('change', function() {
                document.querySelectorAll('.whatsapp-guest-checkbox').forEach(function(box) {
                    box.checked = selectAll.checked;
                });
            });
        })();
    </script>
@endsection
