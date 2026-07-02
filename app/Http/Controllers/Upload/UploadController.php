<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function avatar(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,webp|max:5120',
        ]);

        $user = $request->user();
        $path = 'avatars/' . $user->id . '_' . Str::uuid() . '.' . $request->file('file')->extension();

        Storage::disk('r2')->put($path, file_get_contents($request->file('file')), 'public');

        $url = Storage::disk('r2')->url($path);

        $user->update(['avatar_url' => $url]);

        return response()->json(['url' => $url]);
    }

    public function banner(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,webp|max:5120',
        ]);

        $user = $request->user();
        $path = 'banners/' . $user->id . '_' . Str::uuid() . '.' . $request->file('file')->extension();

        Storage::disk('r2')->put($path, file_get_contents($request->file('file')), 'public');

        $url = Storage::disk('r2')->url($path);

        $user->update(['banner_url' => $url]);

        return response()->json(['url' => $url]);
    }

    public function postImage(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => 'required|array|max:10',
            'files.*' => 'required|file|mimes:jpeg,png,gif,webp|max:10240',
        ]);

        $urls = [];

        foreach ($request->file('files') as $file) {
            $path = 'posts/' . Str::uuid() . '.' . $file->extension();
            Storage::disk('r2')->put($path, file_get_contents($file), 'public');
            $urls[] = Storage::disk('r2')->url($path);
        }

        return response()->json(['urls' => $urls], 201);
    }

    public function emoji(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:png,gif,webp|max:256|dimensions:ratio=1',
            'name' => 'required|string|max:32|alpha_dash',
        ]);

        $path = 'emojis/' . $request->name . '_' . Str::uuid() . '.' . $request->file('file')->extension();

        Storage::disk('r2')->put($path, file_get_contents($request->file('file')), 'public');

        $url = Storage::disk('r2')->url($path);

        return response()->json(['url' => $url, 'name' => $request->name], 201);
    }
}
