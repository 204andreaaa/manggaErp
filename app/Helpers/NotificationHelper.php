<?php

namespace App\Helpers;

use App\Events\ErpRecordChanged;
use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    /**
     * Push a "this record changed" pulse to every logged-in user so list/detail
     * pages can refresh themselves live. Carries no business data beyond
     * type/id/status — pages still re-fetch through their normal, permission-
     * checked routes, so this never bypasses authorization.
     */
    public static function pushLive(string $recordType, int $recordId, string $status): void
    {
        try {
            broadcast(new ErpRecordChanged($recordType, $recordId, $status));
        } catch (\Throwable $e) {
            Log::warning('ErpRecordChanged broadcast failed: '.$e->getMessage());
        }
    }

    /**
     * Kirim notif ke satu user.
     */
    public static function send(int $userId, string $type, string $title, string $body = '', string $url = '', string $refType = '', int $refId = null): void
    {
        $notification = Notification::create([
            'user_id'        => $userId,
            'type'           => $type,
            'title'          => $title,
            'body'           => $body,
            'url'            => $url,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'is_read'        => false,
        ]);

        // Best-effort live push — the notification row above is already saved
        // and is the source of truth (picked up on next page load/refresh
        // regardless). If Reverb is unreachable, swallow the error here so a
        // websocket hiccup never breaks the action that triggered this notice.
        try {
            broadcast(new NotificationCreated($notification));
        } catch (\Throwable $e) {
            Log::warning('NotificationCreated broadcast failed: '.$e->getMessage());
        }
    }

    /**
     * Kirim notif ke semua Superadmin & Admin.
     */
    public static function notifyAdmins(string $type, string $title, string $body = '', string $url = '', string $refType = '', int $refId = null): void
    {
        $admins = User::whereHas('roles', fn($q) => $q->whereIn('slug', ['superadmin', 'admin']))->get();

        foreach ($admins as $admin) {
            static::send($admin->id, $type, $title, $body, $url, $refType, $refId);
        }
    }

    /**
     * Kirim notif ke semua Warehouse Admin di warehouse tertentu.
     */
    public static function notifyWarehouse(int $warehouseId, string $type, string $title, string $body = '', string $url = '', string $refType = '', int $refId = null): void
    {
        $users = User::where('warehouse_id', $warehouseId)
            ->whereHas('roles', fn($q) => $q->where('slug', 'warehouse'))
            ->get();

        foreach ($users as $user) {
            static::send($user->id, $type, $title, $body, $url, $refType, $refId);
        }

        // Superadmin/Admin juga dapat notif
        static::notifyAdmins($type, $title, $body, $url, $refType, $refId);
    }

    /**
     * Kirim notif ke satu user Sales.
     */
    public static function notifySales(int $salesId, string $type, string $title, string $body = '', string $url = '', string $refType = '', int $refId = null): void
    {
        static::send($salesId, $type, $title, $body, $url, $refType, $refId);
    }

    /**
     * Kirim notif ke semua Finance Admin.
     */
    public static function notifyFinance(string $type, string $title, string $body = '', string $url = '', string $refType = '', int $refId = null): void
    {
        $finances = User::whereHas('roles', fn($q) => $q->where('slug', 'finance'))->get();

        foreach ($finances as $user) {
            static::send($user->id, $type, $title, $body, $url, $refType, $refId);
        }

        // Superadmin/Admin juga dapat notif
        static::notifyAdmins($type, $title, $body, $url, $refType, $refId);
    }

    /**
     * Tandai notifikasi sebagai sudah dibaca berdasarkan referensi (agar badge hilang untuk semua admin).
     */
    public static function markAsReadByReference(string $type, string $refType, int $refId): void
    {
        Notification::where('type', $type)
            ->where('reference_type', $refType)
            ->where('reference_id', $refId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
