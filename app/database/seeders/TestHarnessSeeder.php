<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Category;

class TestHarnessSeeder extends Seeder
{
    public const TESTING_USER_ID = 100000;
    public const SAVINGS_ACCOUNT_ID = 100001;
    public const CREDIT_CARD_ACCOUNT_ID = 100002;
    public const CAT1_ID = 100003;
    public const CAT2_ID = 100004;
    public const CAT3_ID = 100005;
    public const SAVINGS_TRANS0_ID = 100006;
    public const SAVINGS_TRANS1_ID = 100007;
    public const SAVINGS_TRANS2_ID = 100008;
    public const CREDIT_TRANS1_ID = 100009;
    public const CREDIT_TRANS2_ID = 100010;
    public const TRANS_DATE0 = '2024-05-01';
    public const TRANS_DATE1 = '2024-05-14';
    public const TRANS_DATE2 = '2024-06-01';
    public const TRANS_DATE3 = '2024-06-14';

    /**
     * Seed the database for feature tests
     *
     * @return void
     */
    public function run()
    {
        $testingUser = User::factory()->create([
            'id' => self::TESTING_USER_ID,
        ]);
        $savingsAcct = Account::factory()->for($testingUser)->create([
            'name' => "My Kick Ass Savings Account",
            'id' => self::SAVINGS_ACCOUNT_ID
        ]);
        $savingsAccntType = $savingsAcct->accountType;
        $savingsAccntType->name = 'Savings';
        $savingsAccntType->asset = true;
        $savingsAccntType->user_id = $testingUser->id;
        $savingsAccntType->save();
        $creditCardAccount = Account::factory()->for($testingUser)->create([
            'name' => "My Credit Card",
            'id' => self::CREDIT_CARD_ACCOUNT_ID
        ]);
        $creditAccountType = $creditCardAccount->accountType;
        $creditAccountType->name = 'Credit';
        $creditAccountType->asset = false;
        $creditAccountType->user_id = $testingUser->id;
        $creditAccountType->save();
        $cat1 = Category::factory()->for($testingUser)->create([
            'id' => self::CAT1_ID,
            'name' => 'cat1',
            'hex_color' => '#000000',
        ]);
        $cat2 = Category::factory()->for($testingUser)->create([
            'id' => self::CAT2_ID,
            'name' => 'cat2',
            'hex_color' => '#ffffff',
        ]);
        $cat3 = Category::factory()->for($testingUser)->create([
            'id' => self::CAT3_ID,
            'name' => 'cat3',
            'hex_color' => '#aaaaaa',
        ]);
        $catType1 = $cat1->categoryType;
        $catType1->user_id = $testingUser->id;
        $catType1->name = 'CatType1';
        $catType1->save();
        $catType2 = $cat2->categoryType;
        $catType2->user_id = $testingUser->id;
        $catType2->name = 'CatType2';
        $catType2->save();
        $cat3->category_type_id = $catType2->id;
        $cat3->save();

        $rawCatPercentage1 = 2513;
        $rawCatPercentage2 = 7487;

        $savingsTransaction0 = Transaction::factory()
            ->for($savingsAcct)
            ->create([
                'id' => self::SAVINGS_TRANS0_ID,
                'transaction_date' => self::TRANS_DATE1,
                'amount' => 42000, // $420.00
                'credit' => true,
                'note' => 'SavingsTransaction0'
            ]);
        // like amounts, we store percentages as integers
        $savingsTransaction0
            ->categories()
            ->save($cat1, ['percentage' => 100 * 100]);
        $savingsTransaction0->save();

        $savingsTransaction1 = Transaction::factory()
            ->for($savingsAcct)
            ->create([
                'id' => self::SAVINGS_TRANS1_ID,
                'transaction_date' => self::TRANS_DATE3,
                'amount' => 223444, // $2233.44
                'credit' => true,
                'note' => 'SavingsTransaction1'
            ]);
        // like amounts, we store percentages as integers
        $savingsTransaction1
            ->categories()
            ->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $savingsTransaction1
            ->categories()
            ->save($cat3, [ 'percentage' => $rawCatPercentage2 ]);
        $savingsTransaction1->save();

        $savingsTransaction2 = Transaction::factory()
            ->for($savingsAcct)
            ->create([
                'id' => self::SAVINGS_TRANS2_ID,
                'transaction_date' => self::TRANS_DATE3,
                'amount' => 25119, // $251.19
                'credit' => false,
                'note' => 'SavingsTransaction2'
            ]);
        $savingsTransaction2
            ->categories()
            ->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $savingsTransaction2
            ->categories()
            ->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $savingsTransaction2->save();

        $creditTransaction1 = Transaction::factory()
            ->for($creditCardAccount)
            ->create([
                'id' => self::CREDIT_TRANS1_ID,
                'transaction_date' => self::TRANS_DATE2,
                'amount' => 52523, // $525.23
                'credit' => false,
                'note' => 'CreditTransaction1'
            ]);
        $creditTransaction1
            ->categories()
            ->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $creditTransaction1
            ->categories()
            ->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $creditTransaction1->save();

        $creditTransaction2 = Transaction::factory()
            ->for($creditCardAccount)
            ->create([
                'id' => self::CREDIT_TRANS2_ID,
                'transaction_date' => self::TRANS_DATE0,
                'amount' => 145634, // $1456.34
                'credit' => false,
                'note' => 'CreditTransaction2'
            ]);
        $creditTransaction2
            ->categories()
            ->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $creditTransaction2
            ->categories()
            ->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $creditTransaction2->save();
    }
}
