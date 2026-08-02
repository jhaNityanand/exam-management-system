<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('backend.coming-soon', [
            'moduleTitle' => 'Notifications',
            'moduleMessage' => 'Notification management will be available in a future update. Email, SMS, and push notifications are planned for a later release.',
        ]);
    }
}
