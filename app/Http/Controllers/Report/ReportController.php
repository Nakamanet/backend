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
            ->where('status', '!=', 'dismissed')
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
            $isDeleted = false;
            $savedContent = null;
            $savedAuthor = null;

            if ($row->reportable_type === 'post') {
                $target = Post::withoutGlobalScope(\App\Models\Scopes\HideToxicContentScope::class)
                    ->withCount('likes')
                    ->with('user')
                    ->find($row->reportable_id);
            } elseif ($row->reportable_type === 'comment') {
                $target = \App\Models\Comment\Comment::withoutGlobalScope(\App\Models\Scopes\HideToxicContentScope::class)
                    ->with('user')
                    ->find($row->reportable_id);
            }

            $latestReport = Report::where('reportable_type', $row->reportable_type)
                ->where('reportable_id', $row->reportable_id)
                ->where('status', '!=', 'dismissed')
                ->latest('created_at')
                ->first();

            $allDetails = Report::where('reportable_type', $row->reportable_type)
                ->where('reportable_id', $row->reportable_id)
                ->pluck('details')
                ->filter()
                ->implode(' ');

            $authorWarned = str_contains($allDetails, '[AUTEUR_AVERTI]') || ($target?->user?->banned_until && $target->user->banned_until > now());

            if (!$target) {
                $isDeleted = true;
                if (preg_match('/\[CONTENU_SUPPRIME:(.*?)\]/s', $allDetails, $matches)) {
                    $decoded = json_decode($matches[1], true);
                    if (is_array($decoded)) {
                        $savedContent = $decoded['content'] ?? null;
                        $savedAuthor = isset($decoded['author']) ? ['username' => $decoded['author']] : null;
                    }
                }
            }

            $cleanDetails = $latestReport?->details;
            if ($cleanDetails) {
                $cleanDetails = preg_replace('/\[AUTEUR_AVERTI\]/', '', $cleanDetails);
                $cleanDetails = preg_replace('/\[CONTENU_SUPPRIME:.*?\]/s', '', $cleanDetails);
                $cleanDetails = trim($cleanDetails);
                if (empty($cleanDetails)) {
                    $cleanDetails = null;
                }
            }

            $targetData = null;
            if ($target) {
                $targetData = [
                    'id' => $target->id,
                    'content' => $target->content,
                    'user' => $target->user,
                    'is_hidden' => $target->is_hidden ?? false,
                ];
            } elseif ($savedContent) {
                $targetData = [
                    'id' => $row->reportable_id,
                    'content' => $savedContent,
                    'user' => $savedAuthor,
                    'is_hidden' => true,
                ];
            }

            return [
                'reportable_type' => $row->reportable_type,
                'reportable_id' => $row->reportable_id,
                'report_count' => $row->report_count,
                'target' => $targetData,
                'is_deleted' => $isDeleted,
                'author_warned' => $authorWarned,
                'latest_reason' => $latestReport?->reason,
                'latest_details' => $cleanDetails,
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
            ->update([
                'status' => 'dismissed',
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

        return response()->json(['message' => 'Signalement supprimé du panneau']);
    }

    public function takeAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reportable_type' => 'required|string',
            'reportable_id' => 'required|integer',
            'action' => 'required|in:delete_content,warn_user,delete_report,dismiss',
        ]);

        $authorId = null;
        $authorUsername = null;
        $content = null;

        if ($validated['reportable_type'] === 'post') {
            $post = Post::withoutGlobalScope(\App\Models\Scopes\HideToxicContentScope::class)
                ->with('user')
                ->find($validated['reportable_id']);
            if ($post) {
                $authorId = $post->user_id;
                $authorUsername = $post->user?->username;
                $content = $post->content;
            }
        } elseif ($validated['reportable_type'] === 'comment') {
            $comment = \App\Models\Comment\Comment::withoutGlobalScope(\App\Models\Scopes\HideToxicContentScope::class)
                ->with('user')
                ->find($validated['reportable_id']);
            if ($comment) {
                $authorId = $comment->user_id;
                $authorUsername = $comment->user?->username;
                $content = $comment->content;
            }
        }

        // Action 1: Delete/Dismiss signalement from panel
        if ($validated['action'] === 'delete_report' || $validated['action'] === 'dismiss') {
            Report::where('reportable_type', $validated['reportable_type'])
                ->where('reportable_id', $validated['reportable_id'])
                ->update([
                    'status' => 'dismissed',
                    'reviewed_at' => now(),
                    'reviewed_by' => $request->user()->id,
                ]);

            return response()->json(['message' => 'Signalement supprimé du panneau de modération']);
        }

        // Action 2: Warn author
        if ($validated['action'] === 'warn_user') {
            if (!$authorId) {
                $allDetails = Report::where('reportable_type', $validated['reportable_type'])
                    ->where('reportable_id', $validated['reportable_id'])
                    ->pluck('details')
                    ->filter()
                    ->implode(' ');
                if (preg_match('/\[CONTENU_SUPPRIME:(.*?)\]/s', $allDetails, $matches)) {
                    $decoded = json_decode($matches[1], true);
                    if (isset($decoded['author_id'])) {
                        $authorId = (int)$decoded['author_id'];
                    }
                }
            }

            if ($authorId) {
                $user = \App\Models\User::find($authorId);
                if ($user) {
                    $user->banned_until = now()->addMinutes(30);
                    $user->save();
                }

                \App\Models\Notification\Notification::create([
                    'recipient_id' => $authorId,
                    'sender_id'    => $request->user()->id,
                    'type'         => 'system_warning',
                    'payload'      => [
                        'message' => 'Votre contenu récent a été signalé car il enfreint nos règles communautaires. En conséquence, vous avez reçu une interdiction temporaire de publier pendant 30 minutes.'
                    ],
                ]);
            }

            // Append [AUTEUR_AVERTI] marker and mark action_taken
            $reports = Report::where('reportable_type', $validated['reportable_type'])
                ->where('reportable_id', $validated['reportable_id'])
                ->get();

            foreach ($reports as $r) {
                if (!str_contains($r->details ?? '', '[AUTEUR_AVERTI]')) {
                    $r->details = trim(($r->details ?? '') . ' [AUTEUR_AVERTI]');
                }
                $r->status = 'action_taken';
                $r->reviewed_at = now();
                $r->reviewed_by = $request->user()->id;
                $r->save();
            }

            return response()->json([
                'message' => 'Auteur averti et suspendu pour 30 minutes',
                'author_warned' => true,
            ]);
        }

        // Action 3: Delete content
        if ($validated['action'] === 'delete_content') {
            $reports = Report::where('reportable_type', $validated['reportable_type'])
                ->where('reportable_id', $validated['reportable_id'])
                ->get();

            $snapshotData = json_encode([
                'content'   => $content,
                'author'    => $authorUsername,
                'author_id' => $authorId,
            ]);

            foreach ($reports as $r) {
                if (!str_contains($r->details ?? '', '[CONTENU_SUPPRIME:')) {
                    $r->details = trim(($r->details ?? '') . " [CONTENU_SUPPRIME:{$snapshotData}]");
                }
                $r->status = 'action_taken';
                $r->reviewed_at = now();
                $r->reviewed_by = $request->user()->id;
                $r->save();
            }

            if ($validated['reportable_type'] === 'post' && isset($post)) {
                $post->delete();
            } elseif ($validated['reportable_type'] === 'comment' && isset($comment)) {
                $comment->delete();
            }

            return response()->json([
                'message' => 'Contenu supprimé de la plateforme avec succès',
                'is_deleted' => true,
            ]);
        }

        return response()->json(['message' => 'Action non reconnue'], 400);
    }
}
