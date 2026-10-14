<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Translate single or batch text.
     */
    public function translate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text'   => 'nullable|string',
            'texts'  => 'nullable|array',
            'source' => 'nullable|string|max:5',
            'target' => 'nullable|string|max:5',
        ]);

        $source = $validated['source'] ?? 'en';
        $target = $validated['target'] ?? 'fr';

        // 1. Batch translation
        if (!empty($validated['texts'])) {
            $translatedTexts = $this->translationService->translateBatch($validated['texts'], $target, $source);
            return response()->json([
                'success' => true,
                'target'  => $target,
                'source'  => $source,
                'results' => $translatedTexts,
            ]);
        }

        // 2. Single text translation
        if (isset($validated['text'])) {
            $translated = $this->translationService->translate($validated['text'], $target, $source);
            return response()->json([
                'success'        => true,
                'target'         => $target,
                'source'         => $source,
                'translatedText' => $translated,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Veuillez fournir le paramètre text ou texts.',
        ], 422);
    }
}
