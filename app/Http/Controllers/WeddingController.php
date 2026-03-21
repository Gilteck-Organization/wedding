<?php

namespace App\Http\Controllers;

use App\Models\Rsvp;
use Illuminate\View\View;

class WeddingController extends Controller
{
    public function index(): View
    {
        $capacity = (int) config('wedding.venue_capacity');
        $seatsReserved = Rsvp::reservedSeatTotal();
        $rsvpCapacityReached = $seatsReserved >= $capacity;

        return view('wedding.index', [
            'rsvpCapacityReached' => $rsvpCapacityReached,
        ]);
    }
}
