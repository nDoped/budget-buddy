# AI Receipt Analysis

Analyze receipt images with OpenAI Vision API to automatically extract line items, prices, categories, and transaction date.

## How it works

1. **Capture or upload** a receipt image on the create/edit transaction form
2. Click **"Analyze with AI"**
3. The image is sent to your backend, which calls OpenAI GPT-4o Vision
4. Extracted fields auto-populate the form:
   - **Total** → sets the transaction amount
   - **Date** → sets the transaction date
   - **Line items** → populate the receipt line items section with prices
   - **Tax** → fills the tax field
   - **Categories** → matched against your existing categories (grouped by type for accuracy)
5. "Enter receipt line items" is automatically checked
6. Adjust as needed, then click **"Calculate Percentages"** to compute category splits

## Architecture

```
CameraComponent.vue (focusMode: 'continuous')
  └─ TransactionFiles.vue
      └─ "Analyze with AI" button
          └─ axios POST → /api/receipt/analyze
              └─ AnalysisController@analyzeReceipt
                  └─ ReceiptAnalyzer service
                      └─ OpenAI GPT-4o Vision API
      └─ emit('analyze-receipt', results)
          └─ TransactionsForm.vue / TransactionEditForm.vue
              ├─ form.amount = total
              ├─ form.transaction_date = date
              └─ :ai-analysis prop → TransactionCategory.vue
                  └─ watcher populates lineItems[], tax, enables receipt mode
```

## Files

| File | Role |
|------|------|
| `app/Services/ReceiptAnalyzer.php` | Sends image to OpenAI with categories grouped by type, parses JSON response |
| `app/Http/Controllers/AnalysisController.php` | Validates input, calls analyzer, returns line items + date |
| `database/migrations/..._add_ai_analysis_to_transaction_images.php` | Adds `ai_analysis` JSON column |
| `app/Models/TransactionImage.php` | `$fillable` + `$casts` for `ai_analysis` |
| `resources/js/Components/CameraComponent.vue` | Camera with continuous autofocus, torch support |
| `resources/js/Components/TransactionFiles.vue` | "Analyze with AI" button, axios API call, event emission |
| `resources/js/Components/TransactionCategory.vue` | Accepts `aiAnalysis` prop, matches categories by name + type, populates line items |
| `config/services.php` | OpenAI config (key + model) |

## Category Matching

Categories are sent to the AI grouped by type in JSON format:

```json
{"Pet Costs": ["Supplies", "Food"], "Extra Expenses": ["Supplies", "Entertainment"]}
```

The AI uses the category type context to disambiguate identical category names (e.g., "Supplies" under "Pet Costs" vs "Extra Expenses").

## Setup

Add to `.env`:

```
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o
```

Run migration:

```sh
php artisan migrate
```

## Vite HMR for mobile testing

In `.env`:

```
VITE_DEV_SERVER_HOST=192.168.1.61
```

Then access the app at `http://<that-ip>` from both desktop and mobile.

## Tests

```sh
vendor/bin/sail artisan test --group=analysis
vendor/bin/sail npm run test -- tests/vitest/Analysis.test.js
```

## API Response Format

```json
{
  "store_name": "Walmart",
  "line_items": [
    { "description": "Milk", "price": 4.99, "suggested_category": "Groceries" },
    { "description": "Bread", "price": 2.49, "suggested_category": "Groceries" }
  ],
  "subtotal": 7.48,
  "tax": 0.60,
  "total": 8.08,
  "date": "2024-05-01"
}
```
