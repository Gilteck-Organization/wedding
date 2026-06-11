<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveRsvpsBulkRequest;
use App\Jobs\SendWhatsappReminderJob;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Services\Whatsapp\WhatsappSendGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RsvpAdminController extends Controller
{
    public function index(): View
    {
        $rsvps = Rsvp::with('guest')
            ->orderByDesc('created_at')
            ->paginate(20);

        $pendingCount = Rsvp::query()
            ->where(function (Builder $builder): void {
                $builder
                    ->whereDoesntHave('guest')
                    ->orWhereHas('guest', fn (Builder $guestQuery): Builder => $guestQuery->where('is_approved', false));
            })
            ->count();

        return view('admin.rsvps.index', [
            'rsvps' => $rsvps,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function approve(Rsvp $rsvp): RedirectResponse
    {
        $guest = $this->approveRsvp($rsvp);

        if (WhatsappSendGuard::isConfigured()) {
            SendWhatsappReminderJob::dispatch($guest)->afterCommit();

            return redirect()
                ->route('admin.rsvps.index')
                ->with('success', 'Guest approved. Reminder and access card are being sent via WhatsApp.');
        }

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', 'Guest approved. WhatsApp is not configured — send from Admin → WhatsApp when ready.');
    }

    public function approveBulk(ApproveRsvpsBulkRequest $request): RedirectResponse
    {
        $action = (string) $request->validated('action');

        $query = Rsvp::query()
            ->where(function (Builder $builder): void {
                $builder
                    ->whereDoesntHave('guest')
                    ->orWhereHas('guest', fn (Builder $guestQuery): Builder => $guestQuery->where('is_approved', false));
            });

        if ($action === 'selected') {
            $query->whereIn('id', $request->validated('rsvp_ids', []));
        }

        $rsvps = $query->orderBy('id')->get();

        if ($rsvps->isEmpty()) {
            return redirect()
                ->route('admin.rsvps.index')
                ->with('success', 'No pending RSVPs matched that action.');
        }

        $approved = 0;
        $whatsappConfigured = WhatsappSendGuard::isConfigured();

        foreach ($rsvps as $rsvp) {
            $guest = $this->approveRsvp($rsvp);
            $approved++;

            if ($whatsappConfigured) {
                SendWhatsappReminderJob::dispatch($guest)->afterCommit();
            }
        }

        $message = $approved.' guest'.($approved === 1 ? '' : 's').' approved.';

        if ($whatsappConfigured) {
            $message .= ' Reminder and access card messages are being queued via WhatsApp.';
        }

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', $message);
    }

    public function resendWhatsapp(Rsvp $rsvp): RedirectResponse
    {
        $guest = $rsvp->guest;

        if ($guest === null || ! $guest->is_approved) {
            return redirect()
                ->route('admin.rsvps.index')
                ->with('success', 'Guest must be approved before re-sending the access card.');
        }

        if (! WhatsappSendGuard::isConfigured()) {
            return redirect()
                ->route('admin.rsvps.index')
                ->with('success', 'WhatsApp is not configured. Set credentials in your environment first.');
        }

        SendWhatsappReminderJob::dispatch($guest, force: true);

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', 'Resending WhatsApp reminder and access card to '.$guest->name.'.');
    }

    public function revokeAttendance(Rsvp $rsvp): RedirectResponse
    {
        DB::transaction(function () use ($rsvp): void {
            $rsvp->update([
                'attendance' => 'no',
                'guest_count' => null,
            ]);

            $guest = $rsvp->guest;
            if ($guest !== null) {
                $guest->update([
                    'is_approved' => false,
                    'qr_code' => null,
                ]);
            }
        });

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', 'Attendance revoked for '.$rsvp->name.'. Access card is no longer valid.');
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Name',
            'Phone',
            'Attendance',
            'Guest count',
            'Approval status',
        ];

        $callback = function () use ($headers): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $rsvps = Rsvp::with('guest')
                ->orderBy('id')
                ->get();

            foreach ($rsvps as $rsvp) {
                $approvalStatus = 'Pending';
                if ($rsvp->guest?->is_approved) {
                    $approvalStatus = 'Approved';
                }

                fputcsv($handle, [
                    $rsvp->name,
                    $rsvp->phone,
                    $rsvp->attendance,
                    $rsvp->guest_count,
                    $approvalStatus,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload(
            $callback,
            'rsvps.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ],
        );
    }

    private function approveRsvp(Rsvp $rsvp): Guest
    {
        return DB::transaction(function () use ($rsvp): Guest {
            $guest = $rsvp->guest;

            if ($guest === null) {
                $guest = Guest::create([
                    'name' => $rsvp->name,
                    'phone' => $rsvp->phone,
                    'email' => null,
                    'is_approved' => true,
                ]);
            } else {
                $guest->is_approved = true;
                $guest->save();
            }

            $guest->refresh();

            $accessCardUrl = route('access-card.verify', $guest);

            $guest->update([
                'qr_code' => $accessCardUrl,
                'is_approved' => true,
            ]);

            $rsvp->guest_id = $guest->id;

            if ($rsvp->attendance === 'no') {
                $rsvp->attendance = 'yes';
                $rsvp->guest_count = $rsvp->guest_count ?? 1;
            }

            $rsvp->save();

            return $guest;
        });
    }
}
