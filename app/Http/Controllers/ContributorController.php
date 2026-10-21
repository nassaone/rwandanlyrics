<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ContributorController extends Controller
{
    public function __invoke(): View
    {
        return view('contributor.index');
    }
}
