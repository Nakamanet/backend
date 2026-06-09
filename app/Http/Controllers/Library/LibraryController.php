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
        $data = array_merge($validated, ['user_id' => $userId]);

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
        $data = array_merge($validated, ['user_id' => $userId]);

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
}
