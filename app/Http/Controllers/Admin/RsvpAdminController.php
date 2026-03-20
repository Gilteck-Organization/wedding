<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RsvpAdminController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $rsvps = Rsvp::with('guest')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.rsvps.index', [
            'rsvps' => $rsvps,
        ]);
    }

    public function approve(Rsvp $rsvp): \Illuminate\Http\RedirectResponse
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

            $accessCardUrl = rtrim(config('app.url'), '/') . '/access-card/' . $guest->id;

            $guest->update([
                'qr_code' => $accessCardUrl,
                'is_approved' => true,
            ]);

            $rsvp->guest_id = $guest->id;
            $rsvp->save();
        });

        return redirect()
            ->route('admin.rsvps.index')
            ->with('success', 'Guest approved. Access card link is ready.');
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
