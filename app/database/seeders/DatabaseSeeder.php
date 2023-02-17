<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AccountType;
use App\Models\Account;
use App\Models\Transaction;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $email = 'notsure@example.org';
        $user = User::factory()->create([
            'name' => 'Not Sure',
            'email' => $email
        ]);
        $checking_type = AccountType::factory()->create([
            'name' => 'Checking',
            'asset' => true,
        ]);
        $savings_type = AccountType::factory()->create([
            'name' => 'Savings',
            'asset' => true,
        ]);
        $loan_type = AccountType::factory()->create([
            'name' => 'Loan',
            'asset' => false,
        ]);
        $credit_type = AccountType::factory()->create([
            'name' => 'Credit',
            'asset' => false,
        ]);
        $checking = Account::factory()->create([
            'name' => 'Test checking account, please ignore',
            'type_id' => $checking_type->id,
            'user_id' => $user->id

        ]);
        $loan = Account::factory()->create([
            'name' => 'Test loan account, please ignore',
            'user_id' => $user->id,
            'type_id' => $loan_type->id
        ]);
        Transaction::factory()->create([
            'amount' => 100,
            'account_id' => $checking->id,
            'transaction_date' => date('Y-m-d')
        ]);
        Transaction::factory()->create([
            'amount' => 100,
            'credit' => true,
            'account_id' => $loan->id,
            'transaction_date' => date('Y-m-d')
        ]);
        $user2 = User::factory()->create([
            'name' => 'Frodo Baggins',
            'email' => 'frodo@example.org'
        ]);
        $savings = Account::factory()->create([
            'name' => 'Test savings account, please ignore',
            'user_id' => 2,
            'type_id' => 2
        ]);
        Transaction::factory()->create([
            'amount' => 100,
            'credit' => true,
            'account_id' => $savings->id,
            'transaction_date' => date('Y-m-d')
        ]);
    }
}
