<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    // TTLs in seconds
    const TTL_SHORT  = 120;  // 2 min  — lists that change often
    const TTL_MEDIUM = 300;  // 5 min  — single resource views
    const TTL_LONG   = 600;  // 10 min — personal libraries

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public static function forget(string|array $keys): void
    {
        foreach ((array) $keys as $key) {
            Cache::forget($key);
        }
    }

    // Key builders — one method per resource so keys are never mistyped
    public static function forumTopics(array $params = []): string
    {
        return 'forum:topics:' . md5(serialize($params));
    }

    public static function forumTopic(int $id): string
    {
        return "forum:topic:{$id}";
    }

    public static function posts(array $params = []): string
    {
        return 'posts:list:' . md5(serialize($params));
    }

    public static function post(int $id): string
    {
        return "posts:post:{$id}";
    }

    public static function postComments(int $id, int $page = 1): string
    {
        return "posts:post:{$id}:comments:page:{$page}";
    }

    public static function userPosts(int $userId, int $page = 1): string
    {
        return "users:{$userId}:posts:page:{$page}";
    }

    public static function animeLibrary(int $userId): string
    {
        return "library:anime:user:{$userId}";
    }

    public static function mangaLibrary(int $userId): string
    {
        return "library:manga:user:{$userId}";
    }

    public static function friends(int $userId): string
    {
        return "friends:user:{$userId}";
    }
}
