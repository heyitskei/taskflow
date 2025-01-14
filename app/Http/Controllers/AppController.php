<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;

class AppController extends Controller
{
    public function show()
    {
        $currentMonth = Carbon::now()->format('F'); // e.g., "November"
        $daysInMonth = Carbon::now()->daysInMonth; // e.g., 30

        return Inertia::render('App', ['currentMonth' => $currentMonth, 'daysInMonth' => $daysInMonth]);
    }
}
