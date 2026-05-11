<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead($id)
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
        return back();
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    }
}
