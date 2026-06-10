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
        $field = $request->hasFile('avatar') ? 'avatar' : 'file';
        $request->validate([
            $field => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        return $this->uploadImage($request, $field, 'avatar', 'avatars');
    }

    public function banner(Request $request): JsonResponse
    {
        $field = $request->hasFile('banner') ? 'banner' : 'file';
        $request->validate([
            $field => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        return $this->uploadImage($request, $field, 'banner', 'banners');
    }

    private function uploadImage(Request $request, string $field, string $urlField, string $folder): JsonResponse
    {
        $user = $request->user();
        $file = $request->file($field);

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $folder . '/' . $filename;

        Storage::disk('r2')->put($path, file_get_contents($file), 'public');

        $url = rtrim(env('CLOUDFLARE_R2_URL'), '/') . '/' . $path;

        $user->update([$urlField . '_url' => $url]);

        return response()->json([
            'message' => ucfirst($urlField) . ' uploaded successfully',
            'url'     => $url,
        ]);
    }
}
