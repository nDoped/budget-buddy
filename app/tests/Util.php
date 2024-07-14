<?php

namespace Tests;
use App\Models\Transaction;
use App\Models\Category;

class Util
{
    public static function deleteMockTransactions(array $transIdsToDelete)
    {
        $mockTransactions = [
            Transaction::find(100006),
            Transaction::find(100007),
            Transaction::find(100008),
            Transaction::find(100009),
            Transaction::find(100010)
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
            Category::find(100003),
            Category::find(100004),
            Category::find(100005)
        ];
        foreach ($mockCats as $cat) {
            if (in_array($cat->id, $catIdsToDelete)) {
                $cat->delete();
            }
        }
    }
}
