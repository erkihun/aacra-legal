<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\SafeUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(12)
            ->through(fn (DatabaseNotification $notification) => $this->transformNotification($notification));

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', __('Notifications marked as read.'));
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->whereKey($notification)
            ->update(['read_at' => now()]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformNotification(DatabaseNotification $notification): array
    {
        $type = (string) ($notification->data['type'] ?? $notification->type);

        return [
            'id' => $notification->id,
            'type' => $type,
            'type_label' => $this->notificationTypeLabel($type),
            'title' => $this->notificationTitle($type, $notification),
            'message' => $this->notificationMessage($type, $notification),
            'data' => $notification->data,
            'related_label' => $notification->data['request_number'] ?? $notification->data['case_number'] ?? null,
            'url' => SafeUrl::appRelativePath(is_string($notification->data['url'] ?? null) ? $notification->data['url'] : null),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    private function notificationTypeLabel(string $type): string
    {
        return match ($type) {
            'advisory.assigned' => __('notifications.feed.types.advisory_assigned'),
            'advisory.overdue' => __('notifications.feed.types.advisory_overdue'),
            'case.assigned' => __('notifications.feed.types.case_assigned'),
            'case.upcoming_hearing' => __('notifications.feed.types.case_upcoming_hearing'),
            'case.appeal_deadline' => __('notifications.feed.types.case_appeal_deadline'),
            default => __('notifications.feed.types.general'),
        };
    }

    private function notificationTitle(string $type, DatabaseNotification $notification): string
    {
        return match ($type) {
            'advisory.assigned' => __('notifications.feed.titles.advisory_assigned'),
            'advisory.overdue' => __('notifications.feed.titles.advisory_overdue'),
            'case.assigned' => __('notifications.feed.titles.case_assigned'),
            'case.upcoming_hearing' => __('notifications.feed.titles.case_upcoming_hearing'),
            'case.appeal_deadline' => __('notifications.feed.titles.case_appeal_deadline'),
            default => (string) ($notification->data['title'] ?? __('Notification')),
        };
    }

    private function notificationMessage(string $type, DatabaseNotification $notification): string
    {
        return match ($type) {
            'advisory.assigned' => __('notifications.feed.messages.advisory_assigned', [
                'subject' => $notification->data['subject'] ?? __('common.not_available'),
                'assigned_by' => $notification->data['assigned_by'] ?? __('common.not_available'),
            ]),
            'advisory.overdue' => __('notifications.feed.messages.advisory_overdue', [
                'due_date' => $notification->data['due_date'] ?? __('common.not_available'),
            ]),
            'case.assigned' => __('notifications.feed.messages.case_assigned', [
                'assigned_by' => $notification->data['assigned_by'] ?? __('common.not_available'),
            ]),
            'case.upcoming_hearing' => __('notifications.feed.messages.case_upcoming_hearing', [
                'next_hearing_date' => $notification->data['next_hearing_date'] ?? __('common.not_available'),
            ]),
            'case.appeal_deadline' => __('notifications.feed.messages.case_appeal_deadline', [
                'appeal_deadline' => $notification->data['appeal_deadline'] ?? __('common.not_available'),
            ]),
            default => (string) ($notification->data['title'] ?? __('Notification')),
        };
    }
}
