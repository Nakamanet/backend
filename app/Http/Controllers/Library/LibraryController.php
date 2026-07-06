<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\AnimeLibraryRequest;
use App\Http\Requests\Library\MangaLibraryRequest;
use App\Models\Library\UserAnimeLibrary;
use App\Models\Library\UserMangaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    /**
     * Normalize a boolean value to a Postgres-safe literal.
     *
     * The Neon/PgBouncer pooler forces PDO::ATTR_EMULATE_PREPARES, which inlines
     * bindings as PHP-typed literals. A PHP int/bool is inlined as 0/1 (an integer
     * literal Postgres refuses to cast into a boolean column), so any boolean written
     * through the Query Builder must be passed as a 'true'/'false' string instead.
     */
    private function normalizeIsPrivate(array $data): array
    {
        if (array_key_exists('is_private', $data)) {
            $data['is_private'] = filter_var($data['is_private'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }

        return $data;
    }

    public function animeIndex(Request $request): JsonResponse
    {
        $library = UserAnimeLibrary::where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->get();

        return response()->json($library);
    }

    public function animeStore(AnimeLibraryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;
        $conditions = ['user_id' => $userId, 'anime_id' => $validated['anime_id']];
        $data = $this->normalizeIsPrivate(array_merge($validated, ['user_id' => $userId]));

        $affected = UserAnimeLibrary::where($conditions)->update($data);

        if ($affected === 0) {
            UserAnimeLibrary::create($data);
        }

        return response()->json(UserAnimeLibrary::where($conditions)->first(), 201);
    }

    public function animeDestroy(Request $request, int $animeId): JsonResponse
    {
        UserAnimeLibrary::where('user_id', $request->user()->id)
            ->where('anime_id', $animeId)
            ->delete();

        return response()->json(['message' => 'Removed from library']);
    }

    public function mangaIndex(Request $request): JsonResponse
    {
        $library = UserMangaLibrary::where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->get();

        return response()->json($library);
    }

    public function mangaStore(MangaLibraryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;
        $conditions = ['user_id' => $userId, 'manga_id' => $validated['manga_id']];
        $data = $this->normalizeIsPrivate(array_merge($validated, ['user_id' => $userId]));

        $affected = UserMangaLibrary::where($conditions)->update($data);

        if ($affected === 0) {
            UserMangaLibrary::create($data);
        }

        return response()->json(UserMangaLibrary::where($conditions)->first(), 201);
    }

    public function mangaDestroy(Request $request, int $mangaId): JsonResponse
    {
        UserMangaLibrary::where('user_id', $request->user()->id)
            ->where('manga_id', $mangaId)
            ->delete();

        return response()->json(['message' => 'Removed from library']);
    }

    public function animeUpdate(Request $request, int $animeId): JsonResponse
    {
        $entry = UserAnimeLibrary::where('user_id', $request->user()->id)
            ->where('anime_id', $animeId)
            ->firstOrFail();

        $entry->update($request->only(['status', 'progress', 'score', 'rewatch_count', 'is_private']));

        return response()->json($entry);
    }

    public function mangaUpdate(Request $request, int $mangaId): JsonResponse
    {
        $entry = UserMangaLibrary::where('user_id', $request->user()->id)
            ->where('manga_id', $mangaId)
            ->firstOrFail();

        $entry->update($request->only(['status', 'progress', 'score', 'reread_count', 'is_private']));

        return response()->json($entry);
    }
}
