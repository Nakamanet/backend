<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $preferences = NotificationPreference::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        return response()->json($preferences);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'sometimes|boolean',
            'friendsRequests' => 'sometimes|boolean',
            'mentions' => 'sometimes|boolean',
            'newSorties' => 'sometimes|boolean',
            'messages' => 'sometimes|boolean',
        ]);

        // map camelCase frontend keys -> snake_case columns
        $mapped = [];
        if (array_key_exists('comment', $validated)) $mapped['comment'] = $validated['comment'];
        if (array_key_exists('friendsRequests', $validated)) $mapped['friends_requests'] = $validated['friendsRequests'];
        if (array_key_exists('mentions', $validated)) $mapped['mentions'] = $validated['mentions'];
        if (array_key_exists('newSorties', $validated)) $mapped['new_sorties'] = $validated['newSorties'];
        if (array_key_exists('messages', $validated)) $mapped['messages'] = $validated['messages'];

        $preferences = NotificationPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $mapped
        );

        return response()->json($preferences);
    }
}
