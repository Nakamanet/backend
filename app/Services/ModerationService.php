<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ModerationService
{
    /**
     * Call OpenAI Moderation API to check if content is toxic.
     * Returns true if the content violates OpenAI's policies.
     */
    public function isFlagged(string $text): bool
    {
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            // If no API key is configured, fallback to allowing the content
            Log::warning('OpenAI API Key is missing. Skipping auto-moderation.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/moderations', [
                'input' => $text
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // The moderation API returns an array of results, usually 1 item
                if (isset($data['results'][0]['flagged'])) {
                    return $data['results'][0]['flagged'];
                }
            }
            
            Log::error('OpenAI Moderation failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('OpenAI Moderation Exception: ' . $e->getMessage());
        }

        // Default to not flagged if API fails, so we don't block innocent users during downtime
        return false;
    }
    
    /**
     * Get the specific categories that were flagged (for reporting)
     */
    public function getFlaggedCategories(string $text): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) return [];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post('https://api.openai.com/v1/moderations', [
                'input' => $text
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['results'][0]['categories'])) {
                    $categories = $data['results'][0]['categories'];
                    $flaggedCategories = [];
                    foreach ($categories as $category => $isFlagged) {
                        if ($isFlagged) {
                            $flaggedCategories[] = $category;
                        }
                    }
                    return $flaggedCategories;
                }
            }
        } catch (\Exception $e) {}

        return [];
    }
}
