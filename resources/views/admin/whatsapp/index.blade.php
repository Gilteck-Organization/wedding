@extends('layouts.admin')

@section('admin_content')
    <div class="mx-auto max-w-6xl px-4 sm:px-6 py-10">
        <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
            <div>
                <h1 class="font-serif text-3xl text-[#2c2418]">WhatsApp delivery</h1>
                <p class="mt-2 text-sm text-[#2c2418]/70">
                    Step 1: welcome reminders. Step 2: access cards. Step 3: thank-you to guests who were checked in at
                    the door.
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
                Reminder / access card WhatsApp is not fully configured. Set
                <code class="text-xs">WHATSAPP_ACCESS_TOKEN</code>,
                <code class="text-xs">WHATSAPP_REMINDER_TEMPLATE_NAME</code>, and
                <code class="text-xs">WHATSAPP_TEMPLATE_NAME</code> in your environment.
            </div>
        @endunless

        @unless ($thankYouConfigured)
            <div class="mt-4 rounded-none border border-[#803b48]/30 bg-[#803b48]/10 px-4 py-3 text-sm text-[#803b48]">
                Thank-you WhatsApp is not configured. Set
                <code class="text-xs">WHATSAPP_THANKYOU_TEMPLATE_NAME=thank_fifi_kiki</code>
                (plus access token and phone number ID).
            </div>
        @endunless

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Approved</p>
                <p class="mt-1 font-serif text-2xl text-[#2c2418]">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Checked in</p>
                <p class="mt-1 font-serif text-2xl text-[#2c2418]">{{ $stats['attended'] }}</p>
            </div>
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Thank-you pending</p>
                <p class="mt-1 font-serif text-2xl text-[#2c2418]">{{ $stats['thankyou_pending'] }}</p>
            </div>
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Thank-you sent</p>
                <p class="mt-1 font-serif text-2xl text-[#946112]">{{ $stats['thankyou_sent'] }}</p>
            </div>
            <div class="rounded-none border border-[#946112]/20 bg-[#fffdf8]/95 px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/50">Failed (any)</p>
                <p class="mt-1 font-serif text-2xl text-[#803b48]">{{ $stats['failed'] }}</p>
            </div>
        </div>

        <form action="{{ route('admin.whatsapp.send') }}" method="POST" class="mt-6 space-y-4" data-no-wired-loading="true">
            @csrf

            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60 mb-2">Step 1 — Welcome reminders</p>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" name="intent" value="reminder:all_pending"
                            class="btn-wired px-5 py-2.5 text-xs sm:text-sm"
                            @disabled(! $whatsappConfigured)
                            onclick="return confirm('Send welcome reminders to all guests who have not received one?')">
                            <span class="btn-wired__text">Send pending reminders</span>
                        </button>
                        <button type="submit" name="intent" value="reminder:all_failed"
                            class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#803b48] border border-[#803b48]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                            @disabled(! $whatsappConfigured)
                            onclick="return confirm('Resend failed reminders?')">
                            Resend failed reminders
                        </button>
                        <button type="submit" name="intent" value="reminder:selected"
                            class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#2c2418] border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                            @disabled(! $whatsappConfigured)
                            onclick="return confirm('Send reminder to selected guests?')">
                            Reminder — selected
                        </button>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60 mb-2">Step 2 — Access cards (after reminders)</p>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" name="intent" value="access_card:all_ready"
                            class="btn-wired px-5 py-2.5 text-xs sm:text-sm"
                            @disabled(! $whatsappConfigured)
                            onclick="return confirm('Send access cards to all guests who received a reminder but not yet a card?')">
                            <span class="btn-wired__text">Send access cards (ready)</span>
                        </button>
                        <button type="submit" name="intent" value="access_card:all_failed"
                            class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#803b48] border border-[#803b48]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                            @disabled(! $whatsappConfigured)
                            onclick="return confirm('Resend failed access cards?')">
                            Resend failed cards
                        </button>
                        <button type="submit" name="intent" value="access_card:selected"
                            class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#2c2418] border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                            @disabled(! $whatsappConfigured)
                            onclick="return confirm('Send access card to selected guests? They must already have a reminder.')">
                            Access card — selected
                        </button>
                        <button type="submit" name="intent" value="access_card:all_approved"
                            class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#2c2418]/70 border border-[#946112]/30 bg-white/60 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                            @disabled(! $whatsappConfigured)
                            onclick="return confirm('Force resend access cards to every guest with a reminder?')">
                            Resend all cards
                        </button>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#2c2418]/60 mb-2">
                        Step 3 — Thank you (only guests scanned at the door)
                    </p>
                    <p class="mb-2 text-xs text-[#2c2418]/55">
                        Uses template <code class="text-[11px]">thank_fifi_kiki</code>. Will not send to guests who were
                        not checked in.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" name="intent" value="thank_you:all_pending"
                            class="btn-wired px-5 py-2.5 text-xs sm:text-sm"
                            @disabled(! $thankYouConfigured)
                            onclick="return confirm({{ \Illuminate\Support\Js::from('Send thank-you to all checked-in guests who have not received one yet ('.$stats['thankyou_pending'].')?') }})">
                            <span class="btn-wired__text">Send thank-you (pending · {{ $stats['thankyou_pending'] }})</span>
                        </button>
                        <button type="submit" name="intent" value="thank_you:all_failed"
                            class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#803b48] border border-[#803b48]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                            @disabled(! $thankYouConfigured)
                            onclick="return confirm('Resend failed thank-you messages?')">
                            Resend failed thank-you
                        </button>
                        <button type="submit" name="intent" value="thank_you:selected"
                            class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#2c2418] border border-[#946112]/40 bg-white/70 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                            @disabled(! $thankYouConfigured)
                            onclick="return confirm('Send thank-you to selected guests? Only checked-in guests will be queued.')">
                            Thank-you — selected
                        </button>
                        <button type="submit" name="intent" value="thank_you:all_approved"
                            class="inline-flex items-center justify-center rounded-none px-5 py-2.5 text-xs font-semibold text-[#2c2418]/70 border border-[#946112]/30 bg-white/60 shadow-sm hover:-translate-y-0.5 transition-all disabled:opacity-50"
                            @disabled(! $thankYouConfigured || ($stats['attended'] ?? 0) === 0)
                            onclick="return confirm({{ \Illuminate\Support\Js::from('Force resend thank-you to ALL '.$stats['attended'].' checked-in guests?') }})">
                            Resend thank-you to all checked-in
                        </button>
                    </div>
                </div>
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
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Checked in</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Reminder</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Access card</th>
                                <th class="px-4 py-3 text-left font-semibold text-[#2c2418]/80">Thank you</th>
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
                                    $thankYouLabel = filled($guest->whatsapp_thankyou_error)
                                        ? ['Failed', 'bg-[#803b48]/15 text-[#803b48] border-[#803b48]/40']
                                        : ($guest->whatsapp_thankyou_sent_at
                                            ? ['Sent', 'bg-[#946112]/12 text-[#946112] border-[#946112]/40']
                                            : ($guest->isQrVerified()
                                                ? ['Pending', 'bg-white/60 text-[#2c2418]/70 border-[#946112]/30']
                                                : ['—', 'bg-white/50 text-[#2c2418]/40 border-[#946112]/15']));
                                    $checkedInLabel = $guest->isQrVerified()
                                        ? ['Yes', 'bg-[#946112]/12 text-[#946112] border-[#946112]/40']
                                        : ['No', 'bg-white/50 text-[#2c2418]/40 border-[#946112]/15'];
                                    $reason = $guest->whatsapp_thankyou_error
                                        ?: $guest->whatsapp_reminder_error
                                        ?: $guest->whatsapp_error;
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
                                            class="inline-flex items-center rounded-none border px-3 py-1 text-[11px] font-semibold {{ $checkedInLabel[1] }}"
                                            @if ($guest->qr_verified_at) title="{{ $guest->qr_verified_at->format('M d, Y g:i A') }}" @endif>
                                            {{ $checkedInLabel[0] }}
                                        </span>
                                    </td>
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
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-none border px-3 py-1 text-[11px] font-semibold {{ $thankYouLabel[1] }}">
                                            {{ $thankYouLabel[0] }}
                                        </span>
                                    </td>
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
