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
            'never_sent' => $guests->filter(fn (Guest $guest): bool => $guest->whatsapp_reminder_sent_at === null)->count(),
            'reminder_only' => $guests->filter(fn (Guest $guest): bool => $guest->whatsapp_reminder_sent_at !== null && $guest->whatsapp_status === null)->count(),
            'sent' => $guests->whereIn('whatsapp_status', ['sent', 'delivered', 'read'])->count(),
            'failed' => $guests->filter(fn (Guest $guest): bool => filled($guest->whatsapp_reminder_error) || in_array($guest->whatsapp_status, ['failed', 'retrying'], true))->count(),
            'delivered' => $guests->whereIn('whatsapp_status', ['delivered', 'read'])->count(),
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

        $action = (string) $request->validated('action');

        $query = Guest::query()
            ->where('is_approved', true);

        $query = match ($action) {
            'selected' => $query->whereIn('id', $request->validated('guest_ids', [])),
            'all_pending' => $query->where(function (Builder $builder): void {
                $builder
                    ->whereNull('whatsapp_reminder_sent_at')
                    ->orWhereNotNull('whatsapp_reminder_error')
                    ->orWhereNull('whatsapp_status')
                    ->orWhere('whatsapp_status', 'failed');
            }),
            'all_failed' => $query->where(function (Builder $builder): void {
                $builder
                    ->whereNotNull('whatsapp_reminder_error')
                    ->orWhereIn('whatsapp_status', ['failed', 'retrying']);
            }),
            'all_approved' => $query,
            default => $query->whereRaw('0 = 1'),
        };

        $guests = $query->get();

        if ($guests->isEmpty()) {
            return redirect()
                ->route('admin.whatsapp.index')
                ->with('success', 'No guests matched that send action.');
        }

        $queued = 0;

        foreach ($guests as $guest) {
            if ($this->shouldSendFullSequence($guest, $action)) {
                SendWhatsappReminderJob::dispatch($guest, force: $this->shouldForceReminder($guest, $action));
                $queued++;

                continue;
            }

            if ($this->shouldSendAccessCardOnly($guest)) {
                SendAccessCardWhatsappJob::dispatch($guest, force: true);
                $queued++;
            }
        }

        if ($queued === 0) {
            return redirect()
                ->route('admin.whatsapp.index')
                ->with('success', 'No guests needed a WhatsApp resend.');
        }

        $label = match ($action) {
            'selected' => 'Selected guests',
            'all_pending' => 'Guests pending reminder or access card',
            'all_failed' => 'Failed guests',
            'all_approved' => 'All approved guests',
            default => 'Guests',
        };

        return redirect()
            ->route('admin.whatsapp.index')
            ->with('success', $label.' ('.$queued.') queued. Reminder goes first, access card follows after a short delay.');
    }

    private function shouldSendFullSequence(Guest $guest, string $action): bool
    {
        if ($action === 'all_approved') {
            return true;
        }

        if (filled($guest->whatsapp_reminder_error)) {
            return true;
        }

        if ($guest->whatsapp_reminder_sent_at === null) {
            return true;
        }

        return false;
    }

    private function shouldSendAccessCardOnly(Guest $guest): bool
    {
        return $guest->whatsapp_reminder_sent_at !== null
            && ! filled($guest->whatsapp_reminder_error)
            && in_array($guest->whatsapp_status, ['failed', 'retrying', null], true);
    }

    private function shouldForceReminder(Guest $guest, string $action): bool
    {
        return $action === 'all_approved'
            || filled($guest->whatsapp_reminder_error)
            || ($action === 'selected' && $guest->whatsapp_reminder_sent_at !== null);
    }
}
