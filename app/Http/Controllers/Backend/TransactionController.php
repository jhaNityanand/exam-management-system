<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        return view('backend.coming-soon', [
            'moduleTitle' => 'Transactions',
            'moduleMessage' => 'Transaction processing and payment verification will be available in a future update. Demo exam purchases currently use the placeholder payment flow.',
        ]);
    }
}
