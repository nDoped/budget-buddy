<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\ReceiptAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalysisController extends Controller
{
    public function analyzeReceipt(Request $request, ReceiptAnalyzer $analyzer)
    {
        $request->validate([
            'image' => ['required', 'string'],
            'force_refresh' => ['nullable', 'boolean'],
        ]);

        $image = $request->input('image');
        $forceRefresh = $request->boolean('force_refresh');

        if (!str_starts_with($image, 'data:image/') && !str_starts_with($image, 'data:application/pdf')) {
            return response()->json(['error' => 'Invalid format. Must be a base64 data URI (image or PDF).'], 422);
        }

        try {
            $accounts = $request->user()->accounts()
                ->where('active', true)
                ->whereNotNull('number')
                ->get(['id', 'name', 'number']);

            $result = $analyzer->analyze($image, $forceRefresh, $accounts->toArray());

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
                'suggested_account_id' => $result['suggested_account_id'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            Log::error('Receipt analysis failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
