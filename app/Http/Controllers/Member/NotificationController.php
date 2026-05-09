<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\HomeworkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead(Request $request, HomeworkNotification $notification): RedirectResponse
    {
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return redirect()->back();
    }
}
