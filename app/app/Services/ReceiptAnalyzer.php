<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Category;

class ReceiptAnalyzer
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o');
    }

    public function analyze(string $imageBase64): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured. Set OPENAI_API_KEY in .env');
        }
        $cats = Category::with('categoryType')->get()->groupBy(fn($c) => $c->categoryType?->name ?? 'Uncategorized')->map(fn($group) => $group->pluck('name')->toArray())->toArray();
        $catsList = json_encode($cats);

        $cacheKey = 'receipt_analysis_' . md5($imageBase64 . $catsList);

        return Cache::remember($cacheKey, 86400, function () use ($catsList, $imageBase64) {
            $prompt = <<<'PROMPT'
You are a receipt analyzer. Extract all line items from this receipt image.
For each line item, return:
- description: the item name
- price: the numeric price (as a float)
- suggested_category: a suggested category that exists the json:
    [CATEGORIES]
- when determining the suggested category for each line item, consider the following:
    - the json is structured as:
        {
            category_type1: [ "category_name1", "category_name2", ... ],
            category_type2: [ "category_name3", "category_name4", ... ],
        }
    - consider the category_type when picking a categort, but do not include the category type name in the suggested_category value
    - also use keywords from the line item description to help determine the best category_name match


Also extract:
- store_name: the store or merchant name
- tax: the tax amount (float or null)
- date: the purchase date in YYYY-MM-DD format (string or null)
- total: the total amount (float or null)

Return ONLY valid JSON with this structure:
{
  "store_name": "Store Name",
  "line_items": [
    { "description": "Item", "price": 9.99, "suggested_category": "Category" }
  ],
  "tax": 0.80,
  "date": "2024-05-01",
  "total": 10.79
}

If you cannot read the receipt clearly, return {"error": "Could not read receipt image clearly"}.
PROMPT;

            $prompt = str_replace('[CATEGORIES]', $catsList, $prompt);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $imageBase64,
                                    'detail' => 'high',
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 2000,
                'temperature' => 0.1,
            ]);

            if ($response->failed()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('AI analysis failed: ' . ($response->json('error.message') ?? 'Unknown error'));
            }

            $content = $response->json('choices.0.message.content');
            $content = trim($content);
            if (str_starts_with($content, '```')) {
                $content = preg_replace('/^```(?:json)?\s*\n?/i', '', $content);
                $content = preg_replace('/\n?```\s*$/', '', $content);
            }

            $result = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse OpenAI response', ['content' => $content]);
                throw new \RuntimeException('Failed to parse AI analysis response');
            }

            return $result;
        });
    }
}
