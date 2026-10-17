<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;

class NotificationController
{
    public function unread(Request $request): Response
    {
        $userId = (int) Auth::id();
        $unreadCount = Notification::getUnreadCount($userId);
        $notifications = Notification::getUnreadForUser($userId, 10);

        return Response::json([
            'ok' => true,
            'count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead(Request $request): Response
    {
        $userId = (int) Auth::id();
        Notification::markAllAsRead($userId);

        if ($request->isAjax() || str_contains($request->header('Accept', ''), 'application/json')) {
            return Response::json(['ok' => true]);
        }

        return Response::redirect($request->header('Referer') ?: url('/dashboard'));
    }

    public function markRead(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        Notification::markAsRead((int)$id, $userId);

        if ($request->isAjax() || str_contains($request->header('Accept', ''), 'application/json')) {
            return Response::json(['ok' => true]);
        }

        return Response::redirect($request->header('Referer') ?: url('/dashboard'));
    }
}
