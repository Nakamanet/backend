<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Report\Report;
use App\Models\Post\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reportable_type' => 'required|in:post,comment',
            'reportable_id' => 'required|integer',
            'reason' => 'nullable|string|max:50',
            'details' => 'nullable|string|max:500',
        ]);

        // confirm the target actually exists, so you can't flood junk report rows
        if ($validated['reportable_type'] === 'post') {
            Post::findOrFail($validated['reportable_id']);
        }

        $report = Report::firstOrCreate(
            [
                'reporter_id' => $request->user()->id,
                'reportable_type' => $validated['reportable_type'],
                'reportable_id' => $validated['reportable_id'],
            ],
            [
                'reason' => $validated['reason'] ?? null,
                'details' => $validated['details'] ?? null,
                'status' => 'pending',
            ]
        );

        return response()->json($report, 201);
    }

    // ===== Admin/moderator endpoints =====

    public function index(Request $request): JsonResponse
    {
        $perPage = 20;
        $currentPage = (int) $request->input('page', 1);

        $baseQuery = Report::select('reportable_type', 'reportable_id', DB::raw('COUNT(*) as report_count'))
            ->where('status', 'pending')
            ->groupBy('reportable_type', 'reportable_id');

        $total = DB::table(DB::raw("({$baseQuery->toSql()}) as sub"))
            ->mergeBindings($baseQuery->getQuery())
            ->count();

        $results = (clone $baseQuery)
            ->orderByDesc('report_count')
            ->forPage($currentPage, $perPage)
            ->get();

        // attach the actual post content + latest report reason for context
        $items = $results->map(function ($row) {
            $target = null;
            if ($row->reportable_type === 'post') {
                $target = Post::withCount('likes')->with('user')->find($row->reportable_id);
            }

            $latestReport = Report::where('reportable_type', $row->reportable_type)
                ->where('reportable_id', $row->reportable_id)
                ->latest('created_at')
                ->first();

            return [
                'reportable_type' => $row->reportable_type,
                'reportable_id' => $row->reportable_id,
                'report_count' => $row->report_count,
                'target' => $target,
                'latest_reason' => $latestReport?->reason,
                'latest_details' => $latestReport?->details,
            ];
        });

        $lastPage = (int) ceil($total / $perPage);

        return response()->json([
            'data' => $items,
            'current_page' => $currentPage,
            'last_page' => max(1, $lastPage),
            'total' => $total,
        ]);
    }

    public function dismiss(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reportable_type' => 'required|string',
            'reportable_id' => 'required|integer',
        ]);

        Report::where('reportable_type', $validated['reportable_type'])
            ->where('reportable_id', $validated['reportable_id'])
            ->where('status', 'pending')
            ->update([
                'status' => 'dismissed',
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

        return response()->json(['message' => 'Reports dismissed']);
    }

    public function takeAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reportable_type' => 'required|string',
            'reportable_id' => 'required|integer',
            'action' => 'required|in:delete_content,warn_user,dismiss',
        ]);

        $authorId = null;

        if ($validated['reportable_type'] === 'post') {
            $post = Post::find($validated['reportable_id']);
            if ($post) {
                $authorId = $post->user_id;
                if ($validated['action'] === 'delete_content') {
                    $post->delete(); // Cascades via model event
                }
            }
        } elseif ($validated['reportable_type'] === 'comment') {
            $comment = \App\Models\Comment\Comment::find($validated['reportable_id']);
            if ($comment) {
                $authorId = $comment->user_id;
                if ($validated['action'] === 'delete_content') {
                    $comment->delete();
                }
            }
        }

        if ($validated['action'] === 'warn_user' && $authorId) {
            \App\Models\Notification\Notification::create([
                'recipient_id' => $authorId,
                'sender_id'    => $request->user()->id,
                'type'         => 'system_warning',
                'payload'      => ['message' => 'Votre contenu récent a été signalé car il enfreint nos règles communautaires.'],
            ]);
        }

        Report::where('reportable_type', $validated['reportable_type'])
            ->where('reportable_id', $validated['reportable_id'])
            ->where('status', 'pending')
            ->update([
                'status' => 'action_taken',
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

        return response()->json(['message' => 'Action taken']);
    }
}
