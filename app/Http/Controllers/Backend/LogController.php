<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(): View
    {
        return view('backend.coming-soon', [
            'moduleTitle' => 'Activity Logs',
            'moduleMessage' => 'Searchable activity logs will be available in a future update. Actor and audit trails already exist on individual records.',
        ]);
    }
}
