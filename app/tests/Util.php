<?php

namespace Tests;
use App\Models\Transaction;
use App\Models\Category;
use Database\Seeders\TestHarnessSeeder;

class Util
{
    public static function deleteMockTransactions(array $transIdsToDelete)
    {
        $mockTransactions = [
            Transaction::find(TestHarnessSeeder::SAVINGS_TRANS0_ID),
            Transaction::find(TestHarnessSeeder::SAVINGS_TRANS1_ID),
            Transaction::find(TestHarnessSeeder::SAVINGS_TRANS2_ID),
            Transaction::find(TestHarnessSeeder::CREDIT_TRANS1_ID),
            Transaction::find(TestHarnessSeeder::CREDIT_TRANS2_ID)
        ];
        foreach ($mockTransactions as $trans) {
            if (in_array($trans->id, $transIdsToDelete)) {
                $trans->delete();
            }
        }
    }

    public static function deleteMockCategories(array $catIdsToDelete)
    {
        $mockCats = [
            Category::find(TestHarnessSeeder::CAT1_ID),
            Category::find(TestHarnessSeeder::CAT2_ID),
            Category::find(TestHarnessSeeder::CAT3_ID)
        ];
        foreach ($mockCats as $cat) {
            if (in_array($cat->id, $catIdsToDelete)) {
                $cat->delete();
            }
        }
    }
}
