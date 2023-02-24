<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class ImportData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:month {fileName} {userId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $cat_colors = [
        'Account Adjustment' => '#ff00ff',
        'AdEnt' => '#5c08a1',
        'Books' => '#ff5900',
        'Booze' => '#a26107',
        'CategoryName' => '#ff00ff',
        'Coffee' => '#462306',
        'EVE' => '#ff00ff',
        'Gas' => '#6c6a6c',
        'Gifts' => '#12af25',
        'Groceries:Bread' => '#ffffff',
        'Groceries:Butter' => '#ffffff',
        'Groceries:Cheese' => '#ffffff',
        'Groceries:Chips/Snacks' => '#ffffff',
        'Groceries:Condiments' => '#ffffff',
        'Groceries:Flour' => '#ffffff',
        'Groceries:Fruits/Vegetables' => '#ffffff',
        'Groceries:Generic' => '#ffffff',
        'Groceries:Meat' => '#ffffff',
        'Groceries:Milk/Creme' => '#ffffff',
        'Groceries:Nut Butter' => '#ffffff',
        'Groceries:Salt/Pepper' => '#ffffff',
        'Hunting' => '#2c6912',
        'Interest:Accrued' => '#ff00ff',
        'Interest:Earned' => '#ff00ff',
        'Isopropyl' => '#ff0000',
        'Moving' => '#876212',
        'Puzzles' => '#00eeff',
        'Recurring:Credit Autopay' => '#ff00ff',
        'Recurring:Insurance' => '#ff00ff',
        'Recurring:Loan' => '#ff00ff',
        'Recurring:Service' => '#ff00ff',
        'Recurring:Utility' => '#ff00ff',
        'Red Tent' => '#ff00ff',
        'Refund' => '#ff00ff',
        'Rent' => '#ff5900',
        'Restaurant' => '#6da6ca',
        'Salary:DC' => '#ff00ff',
        'Salary:Main' => '#ff00ff',
        'Salary:WF' => '#ff00ff',
        'savannah\'s bday...' => '#ff00ff',
        'Security Deposit: Outgoing' => '#ff00ff',
        'Tools' => '#00ffd5',
        'Utility:Electric' => '#ff00ff',
        'Utility:Gas' => '#ff00ff',
        'Utility:Internet' => '#b67cb6',
        'Utility:Sewer' => '#ff00ff',
        'Utility:Water' => '#ff00ff',
        'Vitamins & Minerals' => '#3389e6',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $fileName = $this->argument('fileName');
        $user_id = $this->argument('userId');
        if (($handle = fopen('./storage/imports/' . $fileName, "r")) !== FALSE) {

            $year = substr($fileName, 0, 4);
            $month = substr($fileName, 5, 2);

            $date = \Carbon\Carbon::parse($year."-".$month."-01");
            $start = $date->startOfMonth()->format('Y-m-d H:i:s');
            $end = $date->endOfMonth()->format('Y-m-d H:i:s');
            Transaction::where(function($query) {
                $query->select('user_id')
                    ->from('accounts')
                    ->whereColumn('accounts.id', 'transactions.account_id');
            }, $user_id)
                ->whereBetween('transaction_date', [$start, $end])
                ->delete();

            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                if (! $data[0] || ! $data[1] || ! $data[2] || ! $data[3]) {
                    continue;
                }
                $transaction_date = $year . '-' . $month . '-' .  sprintf('%02d', $data[0]);
                $credit = preg_match('/credit/i', $data[1]) ? true : false;
                $amount = intval(preg_replace('/[,\.]/', '', $data[2]));
                if (! $amount) {
                    continue;
                }

                $los_gatos = json_decode($data[3], true);
                if (is_null($los_gatos)) {
                    $los_gatos = [
                        $data[3] => 100
                    ];
                }

                $acct = Account::where('name', '=', $data[4])
                    ->where('user_id', '=', $user_id)
                    ->first();
                $bank_ident = $data[5];
                $note = $data[6];

                $trans = new Transaction();
                $trans->transaction_date = $transaction_date;
                $trans->amount = $amount;
                $trans->account_id = $acct->id;
                $trans->credit = $credit;
                $trans->note = $note;
                $trans->bank_identifier = $bank_ident;
                $trans->save();
                foreach ($los_gatos as $cat => $percent) {
                    $cat_model = Category::where('name', '=', $cat)->first();
                    if (! $cat_model) {
                        $cat_model = new Category();
                        $cat_model->name = $cat;
                        if (preg_match('/^Utility.*$|^Recurring.*$|^Rent$|^Interest.*$|^Security Deposit.*$|^Salary.*|Account Adjustment|^Interest.*$|^Refund.*$/', $cat)) {
                            $cat_model->include_in_expense_breakdown = false;
                        }
                        $cat_model->user_id = $user_id;
                        if (isset($this->cat_colors[$cat_model->name])) {
                            $cat_model->hex_color = $this->cat_colors[$cat_model->name];
                        }
                        $cat_model->save();
                    }
                    $trans->categories()->save($cat_model, [ 'percentage' => $percent * 100 ]);
                }

                /*
                Log::info([
                    "transaction_date" => $transaction_date,
                    "amount" => $amount,
                    "account" => [
                        'id' => $acct->id,
                        'name' => $acct->name
                    ],
                    'credit' => $credit,
                    'note' => $note,
                    'bank_ident' => $bank_ident,
                    'data' => $data
                ]);
                 */
            }
        }
    }
}
