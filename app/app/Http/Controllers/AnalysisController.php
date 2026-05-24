<?php

namespace App\Http\Controllers;

use App\Services\ReceiptAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalysisController extends Controller
{
    public function analyzeReceipt(Request $request, ReceiptAnalyzer $analyzer)
    {
        $request->validate([
            'image' => ['required', 'string'],
        ]);

        $image = $request->input('image');

        if (!str_starts_with($image, 'data:image/')) {
            return response()->json(['error' => 'Invalid image format. Must be a base64 data URI.'], 422);
        }

        try {
            $result = $analyzer->analyze($image);

            if (isset($result['error'])) {
                return response()->json(['error' => $result['error']], 422);
            }

            return response()->json([
                'store_name' => $result['store_name'] ?? null,
                'line_items' => $result['line_items'] ?? [],
                'subtotal' => $result['subtotal'] ?? null,
                'tax' => $result['tax'] ?? null,
                'total' => $result['total'] ?? null,
                'date' => $result['date'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            Log::error('Receipt analysis failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
