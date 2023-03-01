<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
        $email = 'lespaul366@gmail.com';
        $user = User::create([
            'name' => 'nDoped',
            'email' => $email,
            'password' => Hash::make('spitfire9'),
        ]);
        $checking_type = AccountType::create([
            'name' => 'Checking',
            'asset' => true,
            'user_id' => $user->id
        ]);
        $savings_type = AccountType::create([
            'name' => 'Savings',
            'asset' => true,
            'user_id' => $user->id
        ]);
        $loan_type = AccountType::create([
            'name' => 'Loan',
            'asset' => false,
            'user_id' => $user->id
        ]);
        $credit_type = AccountType::create([
            'name' => 'Credit',
            'asset' => false,
            'user_id' => $user->id
        ]);

        Account::create([
            'name' => 'Discover Checking',
            'url' => 'https://www.discover.com',
            'type_id' => $checking_type->id,
            'user_id' => $user->id,
            'initial_balance' => 508193
        ]);
        Account::create([
            'name' => 'WF Checking',
            'url' => 'https://www.wellsfargo.com',
            'type_id' => $checking_type->id,
            'user_id' => $user->id,
            'initial_balance' => 283219
        ]);
        Account::create([
            'name' => 'PayPal',
            'url' => 'https://www.paypal.com',
            'type_id' => $checking_type->id,
            'user_id' => $user->id,
            'initial_balance' => 0
        ]);
        Account::create([
            'name' => 'Discover Savings Main',
            'url' => 'https://www.discover.com',
            'type_id' => $savings_type->id,
            'user_id' => $user->id,
            'initial_balance' => 976468
        ]);
        Account::create([
            'name' => 'Discover Savings Kifaru',
            'url' => 'https://www.discover.com',
            'type_id' => $savings_type->id,
            'user_id' => $user->id,
            'initial_balance' => 67289
        ]);

        Account::create([
            'name' => 'Discover Credit',
            'url' => 'https://www.discover.com',
            'type_id' => $credit_type->id,
            'user_id' => $user->id,
            'initial_balance' => 211507,
            'interest_rate' => 27.7
        ]);
        Account::create([
            'name' => 'PayPal Credit',
            'url' => 'https://www.paypal.com',
            'type_id' => $credit_type->id,
            'user_id' => $user->id,
            'initial_balance' => 60763,
            'interest_rate' => 27.7
        ]);
        Account::create([
            'name' => 'Care Credit',
            'url' => 'https://consumercenter.mysynchrony.com/consumercenter/login/',
            'type_id' => $credit_type->id,
            'user_id' => $user->id,
            'initial_balance' => 145300,
            'interest_rate' => 27.7
        ]);
        account::create([
            'name' => 'Student Loan',
            'url' => 'https://mygreatlakes.org/educate',
            'type_id' => $loan_type->id,
            'user_id' => $user->id,
            'initial_balance' => 3616505,
            'interest_rate' => 6
        ]);
        Account::create([
            'name' => 'Vehicle Loan',
            'url' => 'https://myaccount.santanderconsumerusa.com/Home/SignIn',
            'type_id' => $loan_type->id,
            'user_id' => $user->id,
            'initial_balance' => 1285068,
            'interest_rate' => 15.5
        ]);
    }
}
