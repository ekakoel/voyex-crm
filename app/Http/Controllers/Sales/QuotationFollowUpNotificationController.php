<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\QuotationFollowUpNotification;
use App\Services\Quotation\QuotationFollowUpAutomationService;
use App\Services\Quotation\QuotationFollowUpNotificationService;
use Illuminate\Http\Request;

class QuotationFollowUpNotificationController extends Controller
{
    public function __construct(
        private readonly QuotationFollowUpAutomationService $automationService,
        private readonly QuotationFollowUpNotificationService $notificationService
    ) {
    }

    public function poll(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['enabled' => false, 'count' => 0, 'items' => []], 401);
        }

        $this->automationService->run((int) $user->id);

        return response()->json($this->notificationService->unreadPayloadForUser($user));
    }

    public function read(Request $request, QuotationFollowUpNotification $notification)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['ok' => false], 401);
        }

        $this->notificationService->markRead($notification, (int) $user->id);

        return response()->json(['ok' => true]);
    }
}
