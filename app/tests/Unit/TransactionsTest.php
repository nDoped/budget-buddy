<?php

namespace Tests\Unit;

//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    #[Group('transactions')]
    public function test_create_recurring_series()
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
        $this->assertCount(2, $createdTrans);
    }

    #[Group('transactions')]
    public function test_create_with_recurring()
    {
        $trans = new Transaction();
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => (new \DateTime())->format('Y-m-d'),
            'amount' => 100,
            'credit' => false,
            'description' => 'test',
            'recurring' => true,
            'recurring_end_date' => (new \DateTime('+1 year'))->format('Y-m-d'),
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
    }

    #[Group('transactions')]
    public function test_create_with_recurring_buddies()
    {
        $trans = new Transaction();
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => (new \DateTime())->format('Y-m-d'),
            'amount' => 100,
            'credit' => false,
            'description' => 'test',
            'trans_buddy' => true,
            'trans_buddy_account' => $this->creditCardAccount->id,
            'trans_buddy_note' => 'buddy note',
            'recurring' => true,
            'recurring_end_date' => (new \DateTime('+1 year'))->format('Y-m-d'),
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
        $this->assertCount(26, $createdTrans);
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
