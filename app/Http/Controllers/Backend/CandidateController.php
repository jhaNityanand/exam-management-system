<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function index(): View
    {
        return view('backend.coming-soon');
    }
}
