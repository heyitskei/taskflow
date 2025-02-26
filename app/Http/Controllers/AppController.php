<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class AppController extends Controller
{
    public function show()
    {
        return Inertia::render('App');
    }
}
