<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveRsvpsBulkRequest;
use App\Http\Requests\Admin\DeleteRsvpsBulkRequest;
use App\Http\Requests\Admin\StoreRsvpFromAdminRequest;
use App\Jobs\SendWhatsappReminderJob;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Services\Whatsapp\WhatsappSendGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RsvpAdminController extends Controller
{
    public function create(): View
    {
        return view('admin.rsvps.create');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $attendance = (string) $request->query('attendance', '');
        $status = (string) $request->query('status', '');
        $whatsappStatus = (string) $request->query('whatsapp_status', '');

        $rsvps = Rsvp::query()
            ->with('guest')
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($attendance, ['yes', 'no'], true), function (Builder $builder) use ($attendance): void {
                $builder->where('attendance', $attendance);
            })
            ->when($status !== '', function (Builder $builder) use ($status): void {
                if ($status === 'approved') {
                    $builder->whereHas('guest', fn (Builder $guestQuery): Builder => $guestQuery->where('is_approved', true));

                    return;
                }

                if ($status === 'pending') {
                    $builder->where(function (Builder $query): void {
                        $query
                            ->whereDoesntHave('guest')
                            ->orWhereHas('guest', fn (Builder $guestQuery): Builder => $guestQuery->where('is_approved', false));
                    });

                    return;
                }

                if ($status === 'revoked') {
                    $builder->where(function (Builder $query): void {
                        $query
                            ->where('attendance', 'no')
                            ->orWhereHas('guest', fn (Builder $guestQuery): Builder => $guestQuery->where('is_approved', false));
                    });
                }
            })
            ->when($whatsappStatus !== '', function (Builder $builder) use ($whatsappStatus): void {
                $builder->whereHas('guest', function (Builder $guestQuery) use ($whatsappStatus): void {
                    if ($whatsappStatus === 'none') {
                        $guestQuery->whereNull('whatsapp_status');

                        return;
                    }

                    $guestQuery->where('whatsapp_status', $whatsappStatus);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $pendingCount = Rsvp::query()
            ->where('attendance', 'yes')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereDoesntHave('guest')
                    ->orWhereHas('guest', fn (Builder $guestQuery): Builder => $guestQuery->where('is_approved', false));
            })
            ->count();

        $trashedCount = Rsvp::onlyTrashed()->count();

        return view('admin.rsvps.index', [
            'rsvps' => $rsvps,
            'pendingCount' => $pendingCount,
            'trashedCount' => $trashedCount,
            'filters' => [
                'search' => $search,
                'attendance' => $attendance,
                'status' => $status,
                'whatsapp_status' => $whatsappStatus,
            ],
        ]);
    }

    public function trashed(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $rsvps = Rsvp::onlyTrashed()
            ->with('guest')
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('deleted_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.rsvps.trashed', [
            'rsvps' => $rsvps,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function restore(int $rsvp): RedirectResponse
    {
        $rsvpModel = Rsvp::onlyTrashed()->findOrFail($rsvp);

        $phoneTaken = Rsvp::query()
            ->where('phone', $rsvpModel->phone)
            ->whereKeyNot($rsvpModel->id)
            ->exists();

        if ($phoneTaken) {
            return redirect()
                ->route('admin.rsvps.trashed')
                ->withErrors([
                    'restore' => 'Cannot restore '.$rsvpModel->name.' — another active RSVP already uses this phone number.',
                ]);
        }

        $name = $rsvpModel->name;
        $rsvpModel->restore();

        return redirect()
            ->route('admin.rsvps.trashed')
            ->with('success', 'RSVP for '.$name.' has been restored.');
    }

    public function storeFromAdmin(StoreRsvpFromAdminRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Rsvp::query()->create([
            'guest_id' => null,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'attendance' => $validated['attendance'],
            'guest_count' => $validated['attendance'] === 'yes' ? 1 : null,
            'message' => null,
        ]);

        return redirect()->route('admin.rsvps.index');
    }

    public function approve(Rsvp $rsvp): RedirectResponse
    {
        if ($rsvp->attendance !== 'yes') {
            return redirect()
                ->route('admin.rsvps.index')
                ->with('success', 'Only guests marked as attending can be approved.');
        }

        $guest = $this->approveRsvp($rsvp);

        if (WhatsappSendGuard::isConfigured()) {
            SendWhatsappReminderJob::dispatch($guest)->afterCommit();

            return redirect()
                ->route('admin.rsvps.index')
                ->with('success', 'Guest approved. Welcome reminder is being sent via WhatsApp. Send access cards later from Admin → WhatsApp.');
        }

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', 'Guest approved. WhatsApp is not configured — send from Admin → WhatsApp when ready.');
    }

    public function approveBulk(ApproveRsvpsBulkRequest $request): RedirectResponse
    {
        $action = (string) $request->validated('action');

        $query = Rsvp::query()
            ->where('attendance', 'yes')
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
                SendWhatsappReminderJob::dispatch($guest);
            }
        }

        $message = $approved.' guest'.($approved === 1 ? '' : 's').' approved.';

        if ($whatsappConfigured) {
            $message .= ' Welcome reminders are being queued via WhatsApp. Send access cards later from Admin → WhatsApp.';
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
            ->with('success', 'Resending WhatsApp welcome reminder to '.$guest->name.'.');
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
                    'qr_verified_at' => null,
                ]);
            }
        });

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', 'Attendance revoked for '.$rsvp->name.'. Access card is no longer valid.');
    }

    public function markAttending(Rsvp $rsvp): RedirectResponse
    {
        DB::transaction(function () use ($rsvp): void {
            $rsvp->update([
                'attendance' => 'yes',
                'guest_count' => $rsvp->guest_count ?? 1,
            ]);
        });

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', $rsvp->name.' is now marked as attending. You can approve this RSVP.');
    }

    public function destroy(Rsvp $rsvp): RedirectResponse
    {
        $name = $rsvp->name;

        $this->softDeleteRsvp($rsvp);

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', 'RSVP for '.$name.' has been deleted.');
    }

    public function destroyBulk(DeleteRsvpsBulkRequest $request): RedirectResponse
    {
        $rsvps = Rsvp::query()
            ->whereIn('id', $request->validated('rsvp_ids'))
            ->orderBy('id')
            ->get();

        if ($rsvps->isEmpty()) {
            return redirect()
                ->route('admin.rsvps.index')
                ->with('success', 'No RSVPs matched that action.');
        }

        foreach ($rsvps as $rsvp) {
            $this->softDeleteRsvp($rsvp);
        }

        $count = $rsvps->count();

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', $count.' RSVP'.($count === 1 ? '' : 's').' deleted.');
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

    private function softDeleteRsvp(Rsvp $rsvp): void
    {
        DB::transaction(function () use ($rsvp): void {
            $guest = $rsvp->guest;

            if ($guest !== null) {
                $guest->update([
                    'is_approved' => false,
                    'qr_code' => null,
                    'qr_verified_at' => null,
                ]);
            }

            $rsvp->delete();
        });
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

            $rsvp->save();

            return $guest;
        });
    }
}
