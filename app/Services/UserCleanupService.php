<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserCleanupService
{
    /**
     * Completely delete a list of user IDs and all their associated content.
     *
     * @param array<int> $userIds
     * @return array<string, int> Statistics of deleted records
     */
    public function purgeUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return DB::transaction(function () use ($userIds) {
            // 1. Gather all associated content IDs
            $postIds = DB::table('Posts')->whereIn('user_id', $userIds)->pluck('id')->all();
            $topicIds = DB::table('Forum_Topics')->whereIn('user_id', $userIds)->pluck('id')->all();

            $allReplyIds = DB::table('Forum_Replies')
                ->whereIn('user_id', $userIds)
                ->when(!empty($topicIds), fn($q) => $q->orWhereIn('topic_id', $topicIds))
                ->pluck('id')
                ->all();

            $allCommentIds = DB::table('Comments')
                ->whereIn('user_id', $userIds)
                ->when(!empty($postIds), fn($q) => $q->orWhereIn('post_id', $postIds))
                ->pluck('id')
                ->all();

            // 2. Break self-referential parent_id cycles
            if (!empty($allReplyIds)) {
                DB::table('Forum_Replies')
                    ->whereIn('id', $allReplyIds)
                    ->whereNotNull('parent_id')
                    ->update(['parent_id' => null]);
            }

            if (!empty($allCommentIds)) {
                DB::table('Comments')
                    ->whereIn('id', $allCommentIds)
                    ->whereNotNull('parent_id')
                    ->update(['parent_id' => null]);
            }

            // 3. Delete Forum relationships
            $deletedPins = 0;
            if (!empty($topicIds) || !empty($userIds)) {
                $deletedPins = DB::table('Forum_User_Pins')
                    ->where(function ($q) use ($userIds, $topicIds) {
                        $q->whereIn('user_id', $userIds);
                        if (!empty($topicIds)) {
                            $q->orWhereIn('topic_id', $topicIds);
                        }
                    })
                    ->delete();
            }

            $deletedViews = 0;
            if (!empty($topicIds) || !empty($userIds)) {
                $deletedViews = DB::table('Forum_Topic_Views')
                    ->where(function ($q) use ($userIds, $topicIds) {
                        $q->whereIn('user_id', $userIds);
                        if (!empty($topicIds)) {
                            $q->orWhereIn('topic_id', $topicIds);
                        }
                    })
                    ->delete();
            }

            $deletedVotes = DB::table('Forum_Votes')
                ->where(function ($q) use ($userIds, $topicIds, $allReplyIds) {
                    $q->whereIn('user_id', $userIds);
                    if (!empty($topicIds)) {
                        $q->orWhere(fn($sq) => $sq->where('target_type', 'topic')->whereIn('target_id', $topicIds));
                    }
                    if (!empty($allReplyIds)) {
                        $q->orWhere(fn($sq) => $sq->where('target_type', 'reply')->whereIn('target_id', $allReplyIds));
                    }
                })
                ->delete();

            $deletedReplies = 0;
            if (!empty($allReplyIds)) {
                $deletedReplies = DB::table('Forum_Replies')->whereIn('id', $allReplyIds)->delete();
            }

            $deletedTopics = 0;
            if (!empty($topicIds)) {
                $deletedTopics = DB::table('Forum_Topics')->whereIn('id', $topicIds)->delete();
            }

            // 4. Delete Post relationships
            $deletedSavedPosts = DB::table('Saved_Posts')
                ->where(function ($q) use ($userIds, $postIds) {
                    $q->whereIn('user_id', $userIds);
                    if (!empty($postIds)) {
                        $q->orWhereIn('post_id', $postIds);
                    }
                })
                ->delete();

            $deletedArchivedPosts = DB::table('Archived_Posts')
                ->where(function ($q) use ($userIds, $postIds) {
                    $q->whereIn('user_id', $userIds);
                    if (!empty($postIds)) {
                        $q->orWhereIn('post_id', $postIds);
                    }
                })
                ->delete();

            $deletedLikes = DB::table('Likes')
                ->where(function ($q) use ($userIds, $postIds) {
                    $q->whereIn('user_id', $userIds);
                    if (!empty($postIds)) {
                        $q->orWhereIn('post_id', $postIds);
                    }
                })
                ->delete();

            $deletedComments = 0;
            if (!empty($allCommentIds)) {
                $deletedComments = DB::table('Comments')->whereIn('id', $allCommentIds)->delete();
            }

            $deletedPosts = 0;
            if (!empty($postIds)) {
                $deletedPosts = DB::table('Posts')->whereIn('id', $postIds)->delete();
            }

            // 5. Delete Reports
            $deletedReports = DB::table('Reports')
                ->where(function ($q) use ($userIds, $postIds, $topicIds, $allReplyIds, $allCommentIds) {
                    $q->whereIn('reporter_id', $userIds)
                      ->orWhereIn('reviewed_by', $userIds)
                      ->orWhere(fn($sq) => $sq->where('reportable_type', 'user')->whereIn('reportable_id', $userIds));

                    if (!empty($postIds)) {
                        $q->orWhere(fn($sq) => $sq->where('reportable_type', 'post')->whereIn('reportable_id', $postIds));
                    }
                    if (!empty($topicIds)) {
                        $q->orWhere(fn($sq) => $sq->where('reportable_type', 'forum_topic')->whereIn('reportable_id', $topicIds));
                    }
                    if (!empty($allReplyIds)) {
                        $q->orWhere(fn($sq) => $sq->where('reportable_type', 'forum_reply')->whereIn('reportable_id', $allReplyIds));
                    }
                    if (!empty($allCommentIds)) {
                        $q->orWhere(fn($sq) => $sq->where('reportable_type', 'comment')->whereIn('reportable_id', $allCommentIds));
                    }
                })
                ->delete();

            // 6. Delete Social / User dependencies
            $deletedFriendships = DB::table('Friendships')
                ->whereIn('requester_id', $userIds)
                ->orWhereIn('addressee_id', $userIds)
                ->delete();

            $deletedNotifications = DB::table('Notifications')
                ->whereIn('recipient_id', $userIds)
                ->orWhereIn('sender_id', $userIds)
                ->delete();

            $deletedAnimeLibrary = DB::table('User_Anime_Library')->whereIn('user_id', $userIds)->delete();
            $deletedMangaLibrary = DB::table('User_Manga_Library')->whereIn('user_id', $userIds)->delete();
            $deletedPreferences = DB::table('notification_preferences')->whereIn('user_id', $userIds)->delete();
            $deletedTokens = DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\\Models\\User')
                ->whereIn('tokenable_id', $userIds)
                ->delete();

            // 7. Finally delete the Users
            $deletedUsers = DB::table('Users')->whereIn('id', $userIds)->delete();

            Log::info("UserCleanupService: Purged " . count($userIds) . " user(s).");

            return [
                'users' => $deletedUsers,
                'posts' => $deletedPosts,
                'comments' => $deletedComments,
                'forum_topics' => $deletedTopics,
                'forum_replies' => $deletedReplies,
                'likes' => $deletedLikes,
                'saved_posts' => $deletedSavedPosts,
                'archived_posts' => $deletedArchivedPosts,
                'forum_votes' => $deletedVotes,
                'forum_views' => $deletedViews,
                'forum_pins' => $deletedPins,
                'reports' => $deletedReports,
                'friendships' => $deletedFriendships,
                'notifications' => $deletedNotifications,
            ];
        });
    }

    /**
     * Purge bot users matching email patterns like @example.org, @example.net, etc.
     *
     * @param string $pattern Email LIKE pattern (e.g. '%@example.%')
     * @return array<string, int>
     */
    public function purgeBotUsers(string $pattern = '%@example.%'): array
    {
        $botUserIds = User::where('email', 'like', $pattern)
            ->where('role', '!=', 'admin') // Safety check: never delete admins
            ->pluck('id')
            ->all();

        return $this->purgeUsers($botUserIds);
    }
}
