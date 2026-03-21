<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Rsvp;
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

        return view('admin.rsvps.index', [
            'rsvps' => $rsvps,
        ]);
    }

    public function approve(Rsvp $rsvp): RedirectResponse
    {
        DB::transaction(function () use ($rsvp): void {
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
        });

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', 'Guest approved. Access card link is ready.');
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
}
