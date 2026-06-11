<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendWhatsappBulkRequest;
use App\Jobs\SendAccessCardWhatsappJob;
use App\Jobs\SendWhatsappReminderJob;
use App\Models\Guest;
use App\Services\Whatsapp\WhatsappSendGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WhatsappAdminController extends Controller
{
    public function index(): View
    {
        $guests = Guest::query()
            ->with('latestRsvp')
            ->where('is_approved', true)
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => $guests->count(),
            'no_reminder' => $guests->filter(fn (Guest $guest): bool => $guest->whatsapp_reminder_sent_at === null)->count(),
            'reminder_only' => $guests->filter(fn (Guest $guest): bool => $guest->whatsapp_reminder_sent_at !== null && $guest->whatsapp_status === null)->count(),
            'cards_sent' => $guests->whereIn('whatsapp_status', ['sent', 'delivered', 'read'])->count(),
            'cards_delivered' => $guests->whereIn('whatsapp_status', ['delivered', 'read'])->count(),
            'failed' => $guests->filter(fn (Guest $guest): bool => filled($guest->whatsapp_reminder_error) || in_array($guest->whatsapp_status, ['failed', 'retrying'], true))->count(),
        ];

        return view('admin.whatsapp.index', [
            'guests' => $guests,
            'stats' => $stats,
            'whatsappConfigured' => WhatsappSendGuard::isConfigured(),
        ]);
    }

    public function send(SendWhatsappBulkRequest $request): RedirectResponse
    {
        if (! WhatsappSendGuard::isConfigured()) {
            return redirect()
                ->route('admin.whatsapp.index')
                ->with('success', 'WhatsApp is not configured. Set WHATSAPP_ACCESS_TOKEN, WHATSAPP_PHONE_NUMBER_ID, WHATSAPP_REMINDER_TEMPLATE_NAME, and WHATSAPP_TEMPLATE_NAME.');
        }

        $phase = (string) $request->validated('phase');
        $action = (string) $request->validated('action');

        $guests = $this->guestsForAction($phase, $action, $request->validated('guest_ids', []));

        if ($guests->isEmpty()) {
            return redirect()
                ->route('admin.whatsapp.index')
                ->with('success', 'No guests matched that action.');
        }

        $queued = 0;

        foreach ($guests as $guest) {
            if ($phase === 'reminder') {
                SendWhatsappReminderJob::dispatch($guest, force: $this->shouldForceReminder($guest, $action));
                $queued++;

                continue;
            }

            if ($guest->whatsapp_reminder_sent_at === null) {
                continue;
            }

            SendAccessCardWhatsappJob::dispatch($guest, force: $this->shouldForceAccessCard($guest, $action));
            $queued++;
        }

        if ($queued === 0) {
            return redirect()
                ->route('admin.whatsapp.index')
                ->with('success', 'No guests were queued. Access cards require a reminder to be sent first.');
        }

        $label = match ([$phase, $action]) {
            ['reminder', 'all_pending'] => 'Pending reminders',
            ['reminder', 'all_failed'] => 'Failed reminders',
            ['reminder', 'all_approved'] => 'All reminders (resend)',
            ['reminder', 'selected'] => 'Selected reminders',
            ['access_card', 'all_ready'] => 'Guests ready for access cards',
            ['access_card', 'all_pending'] => 'Pending access cards',
            ['access_card', 'all_failed'] => 'Failed access cards',
            ['access_card', 'all_approved'] => 'All access cards (resend)',
            ['access_card', 'selected'] => 'Selected access cards',
            default => 'Guests',
        };

        return redirect()
            ->route('admin.whatsapp.index')
            ->with('success', $label.' ('.$queued.') queued.');
    }

    /**
     * @param  array<int, int>  $guestIds
     * @return Collection<int, Guest>
     */
    private function guestsForAction(string $phase, string $action, array $guestIds)
    {
        $query = Guest::query()->where('is_approved', true);

        if ($action === 'selected') {
            return $query->whereIn('id', $guestIds)->get();
        }

        if ($phase === 'reminder') {
            return match ($action) {
                'all_pending' => $query->where(function (Builder $builder): void {
                    $builder
                        ->whereNull('whatsapp_reminder_sent_at')
                        ->orWhereNotNull('whatsapp_reminder_error');
                })->get(),
                'all_failed' => $query->whereNotNull('whatsapp_reminder_error')->get(),
                'all_approved' => $query->get(),
                default => collect(),
            };
        }

        return match ($action) {
            'all_ready', 'all_pending' => $query
                ->whereNotNull('whatsapp_reminder_sent_at')
                ->whereNull('whatsapp_reminder_error')
                ->where(function (Builder $builder): void {
                    $builder
                        ->whereNull('whatsapp_status')
                        ->orWhereIn('whatsapp_status', ['failed', 'retrying']);
                })
                ->get(),
            'all_failed' => $query
                ->whereNotNull('whatsapp_reminder_sent_at')
                ->whereIn('whatsapp_status', ['failed', 'retrying'])
                ->get(),
            'all_approved' => $query
                ->whereNotNull('whatsapp_reminder_sent_at')
                ->get(),
            default => collect(),
        };
    }

    private function shouldForceReminder(Guest $guest, string $action): bool
    {
        return $action === 'all_approved'
            || filled($guest->whatsapp_reminder_error)
            || ($action === 'selected' && $guest->whatsapp_reminder_sent_at !== null);
    }

    private function shouldForceAccessCard(Guest $guest, string $action): bool
    {
        return $action === 'all_approved'
            || in_array($guest->whatsapp_status, ['failed', 'retrying'], true)
            || ($action === 'selected' && $guest->whatsapp_message_id !== null);
    }
}
