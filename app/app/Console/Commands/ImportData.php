<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Account;
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
                $amount = intval(preg_replace('/[,\.]/', '', $data[2]));
                if (! $amount) {
                    continue;
                }

                $acct = Account::where('name', '=', $data[3])
                    ->where('user_id', '=', $user_id)
                    ->first();
                $credit = preg_match('/credit/i', $data[1]) ? true : false;
                $note = $data[5];
                $bank_ident = $data[4];

                $trans = new Transaction();
                $trans->transaction_date = $transaction_date;
                $trans->amount = $amount;
                $trans->account_id = $acct->id;
                $trans->credit = $credit;
                $trans->note = $note;
                $trans->bank_identifier = $bank_ident;
                $trans->save();

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
