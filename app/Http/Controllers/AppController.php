<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;

class AppController extends Controller
{
    public function show()
    {
        $currentMonth = Carbon::now()->format('F');
        $daysInMonth = Carbon::now()->daysInMonth;

        return Inertia::render('App', ['currentMonth' => $currentMonth, 'daysInMonth' => $daysInMonth]);
    }
}
