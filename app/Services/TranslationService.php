<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Translate a single text string from source to target language.
     * Results are cached indefinitely to maximize performance and eliminate redundant requests.
     */
    public function translate(string $text, string $target = 'fr', string $source = 'en'): string
    {
        $trimmed = trim($text);
        if (empty($trimmed)) {
            return $text;
        }

        // Cache key based on source, target and md5 hash of content
        $cacheKey = "trans_{$source}_{$target}_" . md5($trimmed);

        return Cache::rememberForever($cacheKey, function () use ($trimmed, $target, $source) {
            return $this->performTranslation($trimmed, $target, $source);
        });
    }

    /**
     * Translate multiple texts in batch.
     */
    public function translateBatch(array $texts, string $target = 'fr', string $source = 'en'): array
    {
        $results = [];
        foreach ($texts as $key => $text) {
            if (is_string($text) && !empty(trim($text))) {
                $results[$key] = $this->translate($text, $target, $source);
            } else {
                $results[$key] = $text;
            }
        }
        return $results;
    }

    /**
     * Perform translation with primary (Google GTX) and secondary (MyMemory) fallback engines.
     */
    protected function performTranslation(string $text, string $target, string $source): string
    {
        // 1. Try Google Translate Engine (GTX)
        $googleResult = $this->translateViaGoogle($text, $target, $source);
        if ($googleResult !== null) {
            return $googleResult;
        }

        // 2. Try MyMemory Fallback Engine
        $myMemoryResult = $this->translateViaMyMemory($text, $target, $source);
        if ($myMemoryResult !== null) {
            return $myMemoryResult;
        }

        // 3. Fallback to original text if both fail
        return $text;
    }

    /**
     * Primary: Google Translate GTX Engine via optimized cURL
     */
    protected function translateViaGoogle(string $text, string $target, string $source): ?string
    {
        try {
            $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl={$source}&tl={$target}&dt=t&q=" . rawurlencode($text);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json, text/plain, */*',
                'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            ]);

            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($statusCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data[0]) && is_array($data[0])) {
                    $translated = '';
                    foreach ($data[0] as $part) {
                        if (isset($part[0]) && is_string($part[0])) {
                            $translated .= $part[0];
                        }
                    }

                    if (!empty(trim($translated))) {
                        return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Google translate exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Secondary Fallback: MyMemory API
     */
    protected function translateViaMyMemory(string $text, string $target, string $source): ?string
    {
        try {
            // MyMemory works best with segments under 500 chars
            $queryText = mb_substr($text, 0, 500);
            $url = "https://api.mymemory.translated.net/get?q=" . rawurlencode($queryText) . "&langpair={$source}|{$target}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($statusCode === 200 && $response) {
                $data = json_decode($response, true);
                $translated = $data['responseData']['translatedText'] ?? null;
                if (!empty($translated) && stripos($translated, 'MYMEMORY WARNING') === false) {
                    return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        } catch (\Exception $e) {
            Log::warning("MyMemory translate exception: " . $e->getMessage());
        }

        return null;
    }
}
