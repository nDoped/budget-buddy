<?php

namespace Tests\Unit;

//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use \PHPUnit\Framework\Attributes\Group;
use Database\Seeders\TestHarnessSeeder;
use Tests\Util;


class TransactionsTest extends TestCase
{
    use RefreshDatabase;
    private $savingsTransaction0;
    private $savingsTransaction1;
    private $savingsTransaction2;
    private $creditTransaction1;
    private $creditTransaction2;
    private $savingsAccount;
    private $creditCardAccount;
    private $cat1;
    private $cat2;
    private $cat3;
    private $catType1;
    private $catType2;
    private $actualCatPercentage1;
    private $actualCatPercentage2;
    private $user;

    protected function setup(): void
    {
        parent::setUp();
        $this->seed(TestHarnessSeeder::class);
        $this->user = User::find(TestHarnessSeeder::TESTING_USER_ID);
        $this->actingAs($this->user);
        $this->savingsAccount
            = Account::find(TestHarnessSeeder::SAVINGS_ACCOUNT_ID);
        $this->creditCardAccount
            = Account::find(TestHarnessSeeder::CREDIT_CARD_ACCOUNT_ID);

        $this->assertEquals($this->savingsAccount->user_id, $this->user->id);
        $this->cat1 = Category::find(TestHarnessSeeder::CAT1_ID);
        $this->cat2 = Category::find(TestHarnessSeeder::CAT2_ID);
        $this->cat3 = Category::find(TestHarnessSeeder::CAT3_ID);
        $this->catType1 = $this->cat1->categoryType;
        $this->catType2 = $this->cat2->categoryType;

        // 100% cat1
        $this->savingsTransaction0
            = Transaction::find(TestHarnessSeeder::SAVINGS_TRANS0_ID);
        $this->savingsTransaction1
            = Transaction::find(TestHarnessSeeder::SAVINGS_TRANS1_ID);
        // savingsTransaction1 is cat1 and cat3.. we need to get the actual percentages for the tests
        foreach ($this->savingsTransaction1->categories as $cat) {
            $percent = $cat->pivot->percentage;
            switch ($cat->id) {
                case $this->cat1->id:
                    $this->actualCatPercentage1 = $percent / 10000;;
                    break;
                case $this->cat3->id:
                    $this->actualCatPercentage2 = $percent / 10000;;
                    break;
            }
        }

        $this->savingsTransaction2
            = Transaction::find(TestHarnessSeeder::SAVINGS_TRANS2_ID);
        $this->creditTransaction1
            = Transaction::find(TestHarnessSeeder::CREDIT_TRANS1_ID);
        $this->creditTransaction2
            = Transaction::find(TestHarnessSeeder::CREDIT_TRANS2_ID);
    }

    public function test_create_recurring_series_with_invalid_frequency()
    {
        // with invalid frequency
        $this->withoutExceptionHandling();
        try {
            $this->savingsTransaction0->createRecurringSeries(
                null,
                'foo'
            );
        } catch (\Exception $e) {
            $this->assertEquals('Invalid frequency', $e->getMessage());
        }
    }

    #[Group('transactions')]
    public function test_create_monthly_recurring_series()
    {
        $this->savingsTransaction0->transaction_date = '2022-11-30';
        $this->savingsTransaction0->save();
        $this->savingsTransaction0->createRecurringSeries(
            '2023-12-31',
            'monthly'
        );
        $children = $this->savingsTransaction0->children();
        $this->assertCount(13, $children);
        $this->assertEquals('2022-12-30', $children[0]->transaction_date);
        $this->assertEquals('2023-01-30', $children[1]->transaction_date);
        $this->assertEquals('2023-02-28', $children[2]->transaction_date);
        $this->assertEquals('2023-03-30', $children[3]->transaction_date);
        $this->assertEquals('2023-04-30', $children[4]->transaction_date);
        $this->assertEquals('2023-05-30', $children[5]->transaction_date);
        $this->assertEquals('2023-06-30', $children[6]->transaction_date);
        $this->assertEquals('2023-07-30', $children[7]->transaction_date);
        $this->assertEquals('2023-08-30', $children[8]->transaction_date);
        $this->assertEquals('2023-09-30', $children[9]->transaction_date);
        $this->assertEquals('2023-10-30', $children[10]->transaction_date);
        $this->assertEquals('2023-11-30', $children[11]->transaction_date);
        $this->assertEquals('2023-12-30', $children[12]->transaction_date);

        $this->savingsTransaction1->transaction_date = '2022-08-31';
        $this->savingsTransaction1->save();
        $this->savingsTransaction1->createRecurringSeries(
            '2023-12-31',
            'monthly'
        );
        $children = $this->savingsTransaction1->children();
        $this->assertCount(16, $children);
        $this->assertEquals('2022-09-30', $children[0]->transaction_date);
        $this->assertEquals('2022-10-31', $children[1]->transaction_date);
        $this->assertEquals('2022-11-30', $children[2]->transaction_date);
        $this->assertEquals('2022-12-31', $children[3]->transaction_date);
        $this->assertEquals('2023-01-31', $children[4]->transaction_date);
        $this->assertEquals('2023-02-28', $children[5]->transaction_date);
        $this->assertEquals('2023-03-31', $children[6]->transaction_date);
        $this->assertEquals('2023-04-30', $children[7]->transaction_date);
        $this->assertEquals('2023-05-31', $children[8]->transaction_date);
        $this->assertEquals('2023-06-30', $children[9]->transaction_date);
        $this->assertEquals('2023-07-31', $children[10]->transaction_date);
        $this->assertEquals('2023-08-31', $children[11]->transaction_date);
        $this->assertEquals('2023-09-30', $children[12]->transaction_date);
        $this->assertEquals('2023-10-31', $children[13]->transaction_date);
        $this->assertEquals('2023-11-30', $children[14]->transaction_date);
        $this->assertEquals('2023-12-31', $children[15]->transaction_date);

        $this->creditTransaction1->transaction_date = '2022-02-28';
        $this->creditTransaction1->save();
        $this->creditTransaction1->createRecurringSeries(
            '2023-02-27',
            'monthly'
        );
        $children = $this->creditTransaction1->children();
        $this->assertCount(11, $children);
        $this->assertEquals('2022-03-28', $children[0]->transaction_date);
        $this->assertEquals('2022-04-28', $children[1]->transaction_date);
        $this->assertEquals('2022-05-28', $children[2]->transaction_date);
        $this->assertEquals('2022-06-28', $children[3]->transaction_date);
        $this->assertEquals('2022-07-28', $children[4]->transaction_date);
        $this->assertEquals('2022-08-28', $children[5]->transaction_date);
        $this->assertEquals('2022-09-28', $children[6]->transaction_date);
        $this->assertEquals('2022-10-28', $children[7]->transaction_date);
        $this->assertEquals('2022-11-28', $children[8]->transaction_date);
        $this->assertEquals('2022-12-28', $children[9]->transaction_date);
        $this->assertEquals('2023-01-28', $children[10]->transaction_date);

        $this->creditTransaction2->transaction_date = '2022-01-29';
        $this->creditTransaction2->save();
        $this->creditTransaction2->createRecurringSeries(
            '2023-02-27',
            'monthly'
        );
        $children = $this->creditTransaction2->children();
        $this->assertCount(12, $children);
        $this->assertEquals('2022-02-28', $children[0]->transaction_date);
        $this->assertEquals('2022-03-29', $children[1]->transaction_date);
        $this->assertEquals('2022-04-29', $children[2]->transaction_date);
        $this->assertEquals('2022-05-29', $children[3]->transaction_date);
        $this->assertEquals('2022-06-29', $children[4]->transaction_date);
        $this->assertEquals('2022-07-29', $children[5]->transaction_date);
        $this->assertEquals('2022-08-29', $children[6]->transaction_date);
        $this->assertEquals('2022-09-29', $children[7]->transaction_date);
        $this->assertEquals('2022-10-29', $children[8]->transaction_date);
        $this->assertEquals('2022-11-29', $children[9]->transaction_date);
        $this->assertEquals('2022-12-29', $children[10]->transaction_date);
        $this->assertEquals('2023-01-29', $children[11]->transaction_date);
    }

    #[Group('transactions')]
    public function test_create_biweekly_recurring_series()
    {
        $this->savingsTransaction0->transaction_date = '2023-01-06';
        $this->savingsTransaction0->save();
        $this->savingsTransaction0->createRecurringSeries(
            '2023-12-31',
            'biweekly'
        );
        $children = $this->savingsTransaction0->children();
        $this->assertCount(25, $children);
        $this->assertEquals('2023-01-20', $children[0]->transaction_date);
        $this->assertEquals('2023-02-03', $children[1]->transaction_date);
        $this->assertEquals('2023-02-17', $children[2]->transaction_date);
        $this->assertEquals('2023-03-03', $children[3]->transaction_date);
        $this->assertEquals('2023-03-17', $children[4]->transaction_date);
        $this->assertEquals('2023-03-31', $children[5]->transaction_date);
        $this->assertEquals('2023-04-14', $children[6]->transaction_date);
        $this->assertEquals('2023-04-28', $children[7]->transaction_date);
        $this->assertEquals('2023-05-12', $children[8]->transaction_date);
        $this->assertEquals('2023-05-26', $children[9]->transaction_date);
        $this->assertEquals('2023-06-09', $children[10]->transaction_date);
        $this->assertEquals('2023-06-23', $children[11]->transaction_date);
        $this->assertEquals('2023-07-07', $children[12]->transaction_date);
        $this->assertEquals('2023-07-21', $children[13]->transaction_date);
        $this->assertEquals('2023-08-04', $children[14]->transaction_date);
        $this->assertEquals('2023-08-18', $children[15]->transaction_date);
        $this->assertEquals('2023-09-01', $children[16]->transaction_date);
        $this->assertEquals('2023-09-15', $children[17]->transaction_date);
        $this->assertEquals('2023-09-29', $children[18]->transaction_date);
        $this->assertEquals('2023-10-13', $children[19]->transaction_date);
        $this->assertEquals('2023-10-27', $children[20]->transaction_date);
        $this->assertEquals('2023-11-10', $children[21]->transaction_date);
        $this->assertEquals('2023-11-24', $children[22]->transaction_date);
        $this->assertEquals('2023-12-08', $children[23]->transaction_date);
        $this->assertEquals('2023-12-22', $children[24]->transaction_date);
    }
    // @todo
    /* #[Group('transactions')] */
    /* public function test_update_with_images() */
    /* { */
    /* } */

    #[Group('transactions')]
    public function test_create_with_images()
    {
        $base64Meta = 'data:image/png;base64,';
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $base64 = $base64Meta . $base64Image;
        $base64Image_1 = 'iVBORw0KGgoAAAANSUhEUgAAAQAAAAEACAIAAADTED8xAAADMElEQVR4nOzVwQnAIBQFQYXff81RUkQCOyDj1YOPnbXWPmeTRef+/3O/OyBjzh3CD95BfqICMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMO0TAAD//2Anhf4QtqobAAAAAElFTkSuQmCC';
        $base64_1 = $base64Meta . $base64Image_1;
        $trans = new Transaction();
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => '2021-01-01',
            'amount' => 100,
            'credit' => false,
            'description' => 'test',
            'new_images' => [
                [
                    'base64' => $base64,
                    'name' => 'A cool pic'
                ],
                [
                    'base64' => $base64_1,
                    'name' => 'Another cool pic'
                ],
            ],
            'is_credit' => false,
            'categories' => [
                [
                    'cat_data' => [
                        'id' => $this->cat1->id,
                        'name' => $this->cat1->name,
                        'cat_type_id' => $this->catType1->id,
                    ],
                    'percent' => 100
                ]
            ]
        ];
        $createdTrans = $trans->create($data);
        $this->assertCount(1, $createdTrans);
        $newTrans = $createdTrans->first();
        $this->assertEquals($data['account_id'], $newTrans->account_id);
        $this->assertEquals($data['amount'] * 100, $newTrans->amount);
        $this->assertEquals($data['is_credit'], $newTrans->credit);
        $this->assertEquals($data['transaction_date'], $newTrans->transaction_date);

        $this->assertCount(2, $newTrans->transactionImages);
        $expectedImages = [
            $base64Image,
            $base64Image_1
        ];
        foreach($newTrans->transactionImages as $img) {
            $file = Storage::disk('local')->get($img->path);
            $this->assertContains(base64_encode($file), $expectedImages);
            $img->delete();
        }
    }

    #[Group('transactions')]
    public function test_create()
    {
        $trans = new Transaction();
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => '2021-01-01',
            'amount' => 100,
            'credit' => false,
            'description' => 'test',
            'is_credit' => false,
            'categories' => [
                [
                    'cat_data' => [
                        'id' => $this->cat1->id,
                        'name' => $this->cat1->name,
                        'cat_type_id' => $this->catType1->id,
                    ],
                    'percent' => 100
                ]
            ]
        ];
        $createdTrans = $trans->create($data);
        $this->assertCount(1, $createdTrans);
        $newTrans = $createdTrans->first();
        $this->assertEquals($data['account_id'], $newTrans->account_id);
        $this->assertEquals($data['amount'] * 100, $newTrans->amount);
        $this->assertEquals($data['is_credit'], $newTrans->credit);
        $this->assertEquals($data['transaction_date'], $newTrans->transaction_date);
        $this->assertEmpty($newTrans->transactionImages);
    }

    #[Group('transactions')]
    public function test_create_with_buddy()
    {
        $trans = new Transaction();
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => '2021-01-01',
            'amount' => 100,
            'credit' => false,
            'description' => 'test',
            'trans_buddy' => true,
            'trans_buddy_account' => $this->creditCardAccount->id,
            'trans_buddy_note' => 'buddy note',
            'is_credit' => false,
            'categories' => [
                [
                    'cat_data' => [
                        'id' => $this->cat1->id,
                        'name' => $this->cat1->name,
                        'cat_type_id' => $this->catType1->id,
                    ],
                    'percent' => 100
                ]
            ]
        ];
        $createdTrans = $trans->create($data);
        $trans->refresh();
        $this->assertNotNull($trans->buddy_id);
        $this->assertCount(2, $createdTrans);
        $this->assertEquals($data['account_id'], $trans->account_id);
        $this->assertEquals($data['trans_buddy_account'], $trans->buddyTransaction()->account_id);
        $this->assertEquals($data['amount'] * 100, $trans->amount);
        $this->assertEquals(0, $trans->credit);
        $this->assertEquals(1, $trans->buddyTransaction()->credit);
        $this->assertEquals($data['transaction_date'], $trans->transaction_date);
        $this->assertEquals($data['transaction_date'], $trans->buddyTransaction()->transaction_date);
        $this->assertEquals($data['amount'] * 100, $trans->buddyTransaction()->amount);
    }

    #[Group('transactions')]
    public function test_create_with_recurring()
    {
        $trans = new Transaction();
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => '2021-01-31',
            'amount' => 100,
            'credit' => false,
            'note' => 'test',
            'recurring' => true,
            'recurring_end_date' => '2022-01-31',
            'frequency' => 'monthly',
            'is_credit' => false,
            'categories' => [
                [
                    'cat_data' => [
                        'id' => $this->cat1->id,
                        'name' => $this->cat1->name,
                        'cat_type_id' => $this->catType1->id,
                    ],
                    'percent' => 100
                ]
            ]
        ];
        $createdTrans = $trans->create($data);
        $this->assertCount(13, $createdTrans);
        $children = $trans->children();
        $this->assertCount(12, $children);
        $expected_child_dates = [
            '2021-02-28',
            '2021-03-31',
            '2021-04-30',
            '2021-05-31',
            '2021-06-30',
            '2021-07-31',
            '2021-08-31',
            '2021-09-30',
            '2021-10-31',
            '2021-11-30',
            '2021-12-31',
            '2022-01-31',
        ];
        foreach ($trans->children() as $i => $child) {
            $this->assertEquals($expected_child_dates[$i], $child->transaction_date);
            $this->assertEquals($data['amount'] * 100, $child->amount);
            $this->assertEquals(0, $child->credit);
            $this->assertEquals($data['note'], $child->note);
            $this->assertEquals($data['account_id'], $child->account_id);
            $this->assertCount(1, $child->categories);
        }
    }

    #[Group('transactions')]
    public function test_create_with_recurring_buddies()
    {
        $trans = new Transaction();
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => '2021-04-30',
            'amount' => 100,
            'credit' => false,
            'note' => 'test',
            'trans_buddy' => true,
            'trans_buddy_account' => $this->creditCardAccount->id,
            'trans_buddy_note' => 'buddy note',
            'recurring' => true,
            'recurring_end_date' => '2022-04-29',
            'frequency' => 'monthly',
            'is_credit' => false,
            'categories' => [
                [
                    'cat_data' => [
                        'id' => $this->cat1->id,
                        'name' => $this->cat1->name,
                        'cat_type_id' => $this->catType1->id,
                    ],
                    'percent' => 100
                ]
            ]
        ];
        $createdTrans = $trans->create($data);
        $this->assertCount(24, $createdTrans);
        $this->assertEquals($data['account_id'], $trans->account_id);
        $this->assertEquals($data['trans_buddy_account'], $trans->buddyTransaction()->account_id);
        $this->assertEquals($data['amount'] * 100, $trans->amount);
        $this->assertEquals(0, $trans->credit);
        $this->assertEquals(1, $trans->buddyTransaction()->credit);
        $this->assertEquals($data['transaction_date'], $trans->transaction_date);
        $this->assertEquals($data['transaction_date'], $trans->buddyTransaction()->transaction_date);
        $this->assertEquals($data['amount'] * 100, $trans->buddyTransaction()->amount);
        $children = $trans->children();
        $this->assertCount(11, $children);
        $expected_child_dates = [
            '2021-05-30',
            '2021-06-30',
            '2021-07-30',
            '2021-08-30',
            '2021-09-30',
            '2021-10-30',
            '2021-11-30',
            '2021-12-30',
            '2022-01-30',
            '2022-02-28',
            '2022-03-30',
        ];
        foreach ($trans->children() as $i => $child) {
            $this->assertEquals($expected_child_dates[$i], $child->transaction_date);
            $this->assertEquals($data['amount'] * 100, $child->amount);
            $this->assertEquals(0, $child->credit);
            $this->assertEquals($data['note'], $child->note);
            $this->assertEquals($data['account_id'], $child->account_id);
            $this->assertCount(1, $child->categories);

            $this->assertEquals($data['trans_buddy_account'], $child->buddyTransaction()->account_id);
            $this->assertEquals(1, $child->buddyTransaction()->credit);
            $this->assertEquals($expected_child_dates[$i], $child->buddyTransaction()->transaction_date);
        }
    }

    #[Group('transactions')]
    public function test_last_child()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction2->id
        ]);
        $this->assertCount(0, $this->savingsTransaction1->children());
        $this->assertCount(0, $this->savingsTransaction2->children());
        $endDate = new \DateTime($this->savingsTransaction0->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        $this->savingsTransaction0->createRecurringSeries(
            $endDate,
            'quarterly'
        );

        $this->assertEquals(6, $this->user->transactions()->count());
        $lastChild = $this->savingsTransaction0->children()->last();
        foreach ($this->savingsTransaction0->children() as $child) {
            if ($child->id === $lastChild->id) {
                $this->assertTrue($child->isLastChild());
            } else {
                $this->assertFalse($child->isLastChild());
            }
        }
        $this->assertFalse($this->savingsTransaction1->isLastChild());
    }

    #[Group('transactions')]
    public function test_children()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction2->id
        ]);
        $this->assertCount(0, $this->savingsTransaction1->children());
        $this->assertCount(0, $this->savingsTransaction2->children());
        $endDate = new \DateTime($this->savingsTransaction0->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        $this->savingsTransaction0->createRecurringSeries(
            $endDate,
            'biweekly'
        );

        $this->assertEquals(28, $this->user->transactions()->count());
        $endDate = new \DateTime($this->savingsTransaction1->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        $this->savingsTransaction1->createRecurringSeries(
            $endDate,
            'biweekly'
        );
        $this->assertEquals(54, $this->user->transactions()->count());
        $firstTrans0Child = $this->savingsTransaction0->children()->first();
        $lastTrans0Child = $this->savingsTransaction0->children()->last();
        $firstTrans1Child = $this->savingsTransaction1->children()->first();
        $lastTrans1Child = $this->savingsTransaction1->children()->last();
        $this->assertCount(0, $lastTrans0Child->children());
        $this->assertCount(0, $lastTrans1Child->children());
        $firstChild0ChildrenCnt = Transaction::where('parent_id', $firstTrans0Child->parent_id)
            ->where('id', '>', $firstTrans0Child->id)
            ->count();
        $this->assertCount($firstChild0ChildrenCnt, $firstTrans0Child->children());
        $firstChild1ChildrenCnt = Transaction::where('parent_id', $firstTrans1Child->parent_id)
            ->where('id', '>', $firstTrans1Child->id)
            ->count();
        $this->assertCount($firstChild1ChildrenCnt, $firstTrans1Child->children());

        foreach ($firstTrans0Child->children() as $child) {
            $this->assertNotEquals($this->savingsTransaction0->id, $child->id);
            $this->assertEquals($this->savingsTransaction0->id, $child->parent_id);
        }
        foreach ($firstTrans1Child->children() as $child) {
            $this->assertNotEquals($this->savingsTransaction1->id, $child->id);
            $this->assertEquals($this->savingsTransaction1->id, $child->parent_id);
        }
    }
}
