<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(15);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
        return back();
    }

    public function follow(Request $request, $id)
    {
        // 1. Verify ownership: must belong to authenticated user
        $notification = auth()->user()->notifications()->findOrFail($id);

        // 2. Mark as read
        if ($notification->unread()) {
            $notification->markAsRead();
        }

        // 3. Resolve destination link
        $destination = $notification->data['link'] ?? '/';

        // 4. Open redirect protection:
        // Only allow relative application paths or URLs matching the application root host.
        $destination = $this->sanitizeDestination($destination);

        return redirect($destination);
    }

    protected function sanitizeDestination(string $url): string
    {
        $fallback = auth()->user()->role === 'owner' ? route('dashboard') : route('customer.dashboard');

        // Disallow dangerous URI schemes or protocol-relative URLs (e.g. //evil.com, javascript:, data:)
        if (preg_match('/^(?:javascript|data|vbscript):/i', $url) || str_starts_with($url, '//')) {
            return $fallback;
        }

        $parsed = parse_url($url);

        // Relative path (e.g. /customer/dashboard?tab=reservations#reservation-123)
        if (!isset($parsed['host'])) {
            return str_starts_with($url, '/') ? $url : '/' . ltrim($url, '/');
        }

        // Absolute URL: ensure host strictly matches current request host or configured app host
        $currentHost = request()->getHost();
        $configuredHost = config('app.url') ? parse_url(config('app.url'), PHP_URL_HOST) : null;

        if (strcasecmp($parsed['host'], $currentHost) === 0 || ($configuredHost && strcasecmp($parsed['host'], $configuredHost) === 0)) {
            return $url;
        }

        return $fallback;
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    }
}
