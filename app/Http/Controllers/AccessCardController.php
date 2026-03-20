<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\View\View;

class AccessCardController extends Controller
{
    public function show(Guest $guest): View
    {
        $guest->load('latestRsvp');

        return view('wedding.access-card', [
            'guest' => $guest,
        ]);
    }
}
