<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $capacity = (int) config('wedding.venue_capacity');

        $totalRsvps = Rsvp::query()->count();

        $attendanceYes = Rsvp::query()->where('attendance', 'yes')->count();
        $attendanceNo = Rsvp::query()->where('attendance', 'no')->count();

        $seatsReserved = Rsvp::reservedSeatTotal();

        $remainingSlots = max(0, $capacity - $seatsReserved);

        $pendingRsvps = Rsvp::query()
            ->where(function ($query): void {
                $query
                    ->whereNull('guest_id')
                    ->orWhereHas('guest', function ($guestQuery): void {
                        $guestQuery->where('is_approved', false);
                    });
            })
            ->count();

        $approvedGuests = Guest::query()->where('is_approved', true)->count();

        $recentRsvps = Rsvp::query()
            ->with('guest')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'capacity' => $capacity,
            'totalRsvps' => $totalRsvps,
            'attendanceYes' => $attendanceYes,
            'attendanceNo' => $attendanceNo,
            'seatsReserved' => $seatsReserved,
            'remainingSlots' => $remainingSlots,
            'pendingRsvps' => $pendingRsvps,
            'approvedGuests' => $approvedGuests,
            'recentRsvps' => $recentRsvps,
        ]);
    }
}
