<?php

namespace App\Http\Controllers;

use App\Models\SmartNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmartNotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', ['notifications' => $request->user()->smartNotifications()->latest()->paginate(20)]);
    }

    public function read(Request $request, SmartNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);
        $notification->update(['read_at' => now(), 'status' => 'read']);

        return back();
    }
}
