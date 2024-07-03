<?php

namespace Tests\Unit;

//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\FeatureTestSeeder;


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
        $this->seed(FeatureTestSeeder::class);
        $this->user = User::find(100000);
        $this->actingAs($this->user);
        $this->savingsAccount = Account::find(100001);
        $this->creditCardAccount = Account::find(100002);

        $this->assertEquals($this->savingsAccount->user_id, $this->user->id);
        $this->cat1 = Category::find(100003);
        $this->cat2 = Category::find(100004);
        $this->cat3 = Category::find(100005);
        $this->catType1 = $this->cat1->categoryType;
        $this->catType2 = $this->cat2->categoryType;

        // 100% cat1
        $this->savingsTransaction0 = Transaction::find(100006);
        $this->savingsTransaction1 = Transaction::find(100007);
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

        $this->savingsTransaction2 = Transaction::find(100008);
        $this->creditTransaction1 = Transaction::find(100009);
        $this->creditTransaction2 = Transaction::find(100010);
    }

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

    public function test_children()
    {
        $this->_deleteMockTransactions([
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
    private function _deleteMockTransactions(array $transIdsToDelete)
    {
        // clean up the other transactions so they don't interfere with the test
        $mockTransactions = [
            $this->creditTransaction1,
            $this->creditTransaction2,
            $this->savingsTransaction0,
            $this->savingsTransaction1,
            $this->savingsTransaction2
        ];
        foreach ($mockTransactions as $trans) {
            if (in_array($trans->id, $transIdsToDelete)) {
                $trans->delete();
            }
        }
    }
}
