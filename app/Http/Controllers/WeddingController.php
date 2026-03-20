<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WeddingController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('wedding.index');
    }
}
