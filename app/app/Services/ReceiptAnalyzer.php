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

    public function analyze(string $dataUri): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured. Set OPENAI_API_KEY in .env');
        }

        $cats = Category::with('categoryType')->where('active', true)->get()->groupBy(fn($c) => $c->categoryType?->name ?? 'Uncategorized')->map(fn($group) => $group->pluck('name')->toArray())->toArray();
        $catsList = json_encode($cats);

        $cacheKey = 'receipt_analysis_' . md5($dataUri . $catsList);
        // Cache::forget($cacheKey); // Clear cache for testing - remove this line in production

        return Cache::remember($cacheKey, 86400, function () use ($catsList, $dataUri) {
            $images = $this->ensureImages($dataUri);
            $prompt = <<<'PROMPT'
You are a receipt analyzer. Extract all line items from this receipt image(s).
Some receipts may span multiple pages — treat all images as part of the same receipt.
- Only extract line items that are purchases (do not include "savings" or line items that are in bold or not aligned with the rest of the prices)
- For each line item, return:
    - price: the numeric price (as a float)
    - suggested_category: a suggested_category that exists in this json:
        [CATEGORIES]
        - the json is structured as:
            {
                category type name: [ "category name 1", "category name 2", ... ],
            }
        - do NOT use the category type's name for the suggested_category value, but consider its value when picking the best category name match for the line item
        - use keywords from the line item name to help determine the best category name match as well

Also extract:
- tax: the tax amount (float or null)
- date: the purchase date in YYYY-MM-DD format (string or null)
- total: the total amount (float or null)

Return ONLY valid JSON with this structure:
{
  "line_items": [
    { "price": 9.99, "suggested_category": "category name4" }
  ],
  "tax": 0.80,
  "date": "2024-05-01",
  "total": 10.79
}

If you cannot read the receipt clearly, return {"error": "Could not read receipt image clearly"}.
PROMPT;

            $prompt = str_replace('[CATEGORIES]', $catsList, $prompt);

            $contentItems = [
                [
                    'type' => 'input_text',
                    'text' => $prompt,
                ],
            ];
            foreach ($images as $imageData) {
                $contentItems[] = [
                    'type' => 'input_image',
                    'image_url' => $imageData,
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/responses', [
                'model' => $this->model,
                'input' => [
                    [
                        'role' => 'user',
                        'content' => $contentItems,
                    ],
                ],
                'max_output_tokens' => 2000,
                'temperature' => 0.1,
            ]);

            if ($response->failed()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('AI analysis failed: ' . ($response->json('error.message') ?? 'Unknown error'));
            }

            $content = $response->json('output.0.content.0.text');
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

    private function ensureImages(string $dataUri): array
    {
        if (!str_starts_with($dataUri, 'data:application/pdf')) {
            return [$dataUri];
        }

        $base64 = substr($dataUri, strpos($dataUri, ';base64,') + 8);
        $pdfData = base64_decode($base64, true);
        if ($pdfData === false) {
            throw new \RuntimeException('Invalid PDF base64 data');
        }

        $tempDir = storage_path('app/tmp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfPath = $tempDir . '/' . uniqid('pdf_', true) . '.pdf';
        $pngPattern = $tempDir . '/' . uniqid('pdf_', true) . '_page_%d.png';

        try {
            file_put_contents($pdfPath, $pdfData);

            $escapedPdf = escapeshellarg($pdfPath);
            $escapedPattern = escapeshellarg($pngPattern);
            $cmd = "/usr/bin/gs -dNOPAUSE -dBATCH -sDEVICE=png16m -r150 -sOutputFile=$escapedPattern $escapedPdf 2>&1";
            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                throw new \RuntimeException('Failed to convert PDF to image: ' . implode("\n", $output));
            }

            $images = [];
            $pageNum = 1;
            $pngPath = sprintf($pngPattern, $pageNum);

            while (file_exists($pngPath)) {
                $pngData = file_get_contents($pngPath);
                if ($pngData === false) {
                    throw new \RuntimeException("Failed to read PDF page $pageNum image");
                }
                $images[] = 'data:image/png;base64,' . base64_encode($pngData);
                unlink($pngPath);
                $pageNum++;
                $pngPath = sprintf($pngPattern, $pageNum);
            }

            if (empty($images)) {
                throw new \RuntimeException('No pages found in PDF');
            }

            return $images;
        } finally {
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        }
    }
}
