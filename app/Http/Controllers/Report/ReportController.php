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
        // group reports by target, ordered by report count desc (most-flagged first)
        $grouped = Report::select('reportable_type', 'reportable_id', DB::raw('COUNT(*) as report_count'))
            ->where('status', 'pending')
            ->groupBy('reportable_type', 'reportable_id')
            ->orderByDesc('report_count')
            ->paginate(20);

        // attach the actual post content + latest report reason for context
        $items = collect($grouped->items())->map(function ($row) {
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

        return response()->json([
            'data' => $items,
            'current_page' => $grouped->currentPage(),
            'last_page' => $grouped->lastPage(),
            'total' => $grouped->total(),
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

        if ($validated['action'] === 'delete_content' && $validated['reportable_type'] === 'post') {
            $post = Post::find($validated['reportable_id']);

            if ($post) {
                DB::transaction(function () use ($post) {
                    // delete dependents first — Posts has no ON DELETE CASCADE
                    $post->comments()->delete();
                    $post->likes()->delete();
                    $post->savedBy()->detach();
                    $post->archivedBy()->detach();
                    $post->delete();
                });
            }
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
