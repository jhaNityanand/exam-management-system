<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        return view('backend.coming-soon');
    }
}
