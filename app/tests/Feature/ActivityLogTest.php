<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Category;
use App\Models\CategoryType;
use App\Models\Transaction;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use \PHPUnit\Framework\Attributes\Group;
use Database\Seeders\TestHarnessSeeder;
use Tests\Util;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $savingsAccount;
    private Account $creditCardAccount;
    private Category $cat1;
    private Category $cat2;
    private Category $cat3;
    private CategoryType $catType1;
    private CategoryType $catType2;
    private Transaction $savingsTransaction0;

    protected function setup(): void
    {
        parent::setUp();
        $this->seed(TestHarnessSeeder::class);
        $this->user = User::find(TestHarnessSeeder::TESTING_USER_ID);
        $this->actingAs($this->user);
        $this->savingsAccount = Account::find(TestHarnessSeeder::SAVINGS_ACCOUNT_ID);
        $this->creditCardAccount = Account::find(TestHarnessSeeder::CREDIT_CARD_ACCOUNT_ID);
        $this->cat1 = Category::find(TestHarnessSeeder::CAT1_ID);
        $this->cat2 = Category::find(TestHarnessSeeder::CAT2_ID);
        $this->cat3 = Category::find(TestHarnessSeeder::CAT3_ID);
        $this->catType1 = $this->cat1->categoryType;
        $this->catType2 = $this->cat2->categoryType;
        $this->savingsTransaction0 = Transaction::find(TestHarnessSeeder::SAVINGS_TRANS0_ID);
    }

    private function assertLastLog(string $expectedType, string $expectedDescriptionSubset): void
    {
        $this->user->refresh();
        $log = ActivityLog::where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($log, 'Expected an activity log entry but none was found');
        $this->assertEquals($expectedType, $log->log_type);
        $this->assertStringContainsString($expectedDescriptionSubset, $log->description);
    }

    #[Group('activity_logs')]
    public function test_activity_log_service_log_method(): void
    {
        ActivityLogService::log('test_event', 'A test log entry', ['key' => 'value']);

        $log = ActivityLog::where('user_id', $this->user->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('test_event', $log->log_type);
        $this->assertEquals('A test log entry', $log->description);
        $this->assertEquals(['key' => 'value'], $log->properties);
    }

    #[Group('activity_logs')]
    public function test_logs_transaction_created(): void
    {
        $this->post('/transactions/store', [
            'account_id' => $this->savingsAccount->id,
            'amount' => 500,
            'credit' => true,
            'transaction_date' => '2025-01-01',
            'note' => 'Log test transaction',
            'trans_buddy' => false,
            'recurring' => false,
            'categories' => [
                ['cat_data' => ['cat_id' => $this->cat1->id, 'name' => $this->cat1->name, 'hex_color' => $this->cat1->hex_color], 'percent' => 100],
            ],
        ]);

        $this->assertLastLog('transaction_created', 'Created transaction');
    }

    #[Group('activity_logs')]
    public function test_logs_transaction_updated(): void
    {
        $this->post('/transactions/update/' . $this->savingsTransaction0->id, [
            'account_id' => $this->creditCardAccount->id,
            'amount' => 999,
            'credit' => true,
            'transaction_date' => '2025-06-01',
            'note' => 'Updated for log test',
            'categories' => [],
        ]);

        $this->assertLastLog('transaction_updated', 'Updated transaction');
    }

    #[Group('activity_logs')]
    public function test_logs_transaction_deleted(): void
    {
        Util::deleteMockTransactions([
            TestHarnessSeeder::SAVINGS_TRANS1_ID,
            TestHarnessSeeder::SAVINGS_TRANS2_ID,
            TestHarnessSeeder::CREDIT_TRANS1_ID,
            TestHarnessSeeder::CREDIT_TRANS2_ID,
        ]);

        $this->delete('/transactions/destroy/' . $this->savingsTransaction0->id);

        $this->assertLastLog('transaction_deleted', 'Deleted transaction');
    }

    #[Group('activity_logs')]
    public function test_logs_category_created(): void
    {
        $this->post('/settings/store_category', [
            'name' => 'Log Test Category',
            'hex_color' => '#ff00ff',
            'category_type' => $this->catType1->id,
        ]);

        $this->assertLastLog('category_created', 'Created category');
    }

    #[Group('activity_logs')]
    public function test_logs_category_updated(): void
    {
        $this->patch('/categories/update/' . $this->cat1->id, [
            'name' => 'Updated Cat Name',
            'hex_color' => '#111111',
            'category_type' => $this->catType2->id,
            'active' => false,
        ]);

        $this->assertLastLog('category_updated', 'Updated category');
    }

    #[Group('activity_logs')]
    public function test_logs_category_merged(): void
    {
        $this->post('/categories/merge/' . $this->cat1->id, [
            'target_category_id' => $this->cat2->id,
        ]);

        $this->assertLastLog('category_merged', 'Merged category');
        $log = ActivityLog::where('user_id', $this->user->id)->latest('id')->first();
        $this->assertEquals($this->cat1->id, $log->properties['source_id']);
        $this->assertEquals($this->cat2->id, $log->properties['target_id']);
    }

    #[Group('activity_logs')]
    public function test_logs_category_deleted(): void
    {
        Util::deleteMockTransactions([
            TestHarnessSeeder::SAVINGS_TRANS0_ID,
            TestHarnessSeeder::SAVINGS_TRANS1_ID,
            TestHarnessSeeder::SAVINGS_TRANS2_ID,
            TestHarnessSeeder::CREDIT_TRANS1_ID,
            TestHarnessSeeder::CREDIT_TRANS2_ID,
        ]);

        $this->delete('/categories/destroy/' . $this->cat1->id);

        $this->assertLastLog('category_deleted', 'Deleted category');
    }

    #[Group('activity_logs')]
    public function test_logs_category_type_created(): void
    {
        $this->post('/settings/store_category_type', [
            'name' => 'Log Test Cat Type',
            'hex_color' => '#aabbcc',
        ]);

        $this->assertLastLog('category_type_created', 'Created category type');
    }

    #[Group('activity_logs')]
    public function test_logs_category_type_updated(): void
    {
        $this->patch('/category_types/update/' . $this->catType1->id, [
            'name' => 'Updated Type Name',
            'hex_color' => '#ff0000',
        ]);

        $this->assertLastLog('category_type_updated', 'Updated category type');
    }

    #[Group('activity_logs')]
    public function test_logs_category_type_deleted(): void
    {
        Util::deleteMockCategories([$this->cat1->id]);

        $this->delete('/category_types/destroy/' . $this->catType1->id);

        $this->assertLastLog('category_type_deleted', 'Deleted category type');
    }

    #[Group('activity_logs')]
    public function test_logs_account_created(): void
    {
        $this->post('/settings/store_account', [
            'name' => 'Log Test Account',
            'type' => $this->savingsAccount->type_id,
        ]);

        $this->assertLastLog('account_created', 'Created account');
    }

    #[Group('activity_logs')]
    public function test_logs_account_updated(): void
    {
        $this->patch('/settings/update_account/' . $this->savingsAccount->id, [
            'name' => 'Updated Account Name',
            'type' => $this->savingsAccount->type_id,
            'url' => 'https://example.com',
        ]);

        $this->assertLastLog('account_updated', 'Updated account');
    }

    #[Group('activity_logs')]
    public function test_logs_account_delete_blocked(): void
    {
        $response = $this->delete('/settings/destroy_account/' . $this->savingsAccount->id);
        $response->assertSessionHasErrors();

        $this->assertCount(0, ActivityLog::where('user_id', $this->user->id)->get());
    }

    #[Group('activity_logs')]
    public function test_logs_account_deleted(): void
    {
        $acct = Account::factory()->for($this->user)->create([
            'name' => 'Temp Account For Deletion',
        ]);

        $this->delete('/settings/destroy_account/' . $acct->id);

        $this->assertLastLog('account_deleted', 'Deleted account');
    }

    #[Group('activity_logs')]
    public function test_logs_account_type_created(): void
    {
        $this->post('/settings/store_account_type', [
            'name' => 'Log Test Acct Type',
            'asset' => true,
        ]);

        $this->assertLastLog('account_type_created', 'Created account type');
    }

    #[Group('activity_logs')]
    public function test_update_log_contains_changes(): void
    {
        $this->post('/transactions/update/' . $this->savingsTransaction0->id, [
            'account_id' => $this->creditCardAccount->id,
            'amount' => 999,
            'credit' => true,
            'transaction_date' => '2025-06-01',
            'note' => 'Updated for log test',
            'categories' => [],
        ]);

        $log = ActivityLog::where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($log->properties);
        $this->assertArrayHasKey('amount', $log->properties);
        $this->assertEquals(42000, $log->properties['amount']['old']);
        $this->assertEquals(99900, $log->properties['amount']['new']);
    }

    #[Group('activity_logs')]
    public function test_update_log_contains_category_changes(): void
    {
        $this->post('/transactions/update/' . $this->savingsTransaction0->id, [
            'account_id' => $this->savingsAccount->id,
            'amount' => 420,
            'credit' => true,
            'transaction_date' => '2025-06-01',
            'note' => 'Updated with cat changes',
            'categories' => [
                ['cat_data' => ['cat_id' => $this->cat2->id, 'name' => $this->cat2->name, 'hex_color' => $this->cat2->hex_color], 'percent' => 60],
                ['cat_data' => ['cat_id' => $this->cat3->id, 'name' => $this->cat3->name, 'hex_color' => $this->cat3->hex_color], 'percent' => 40],
            ],
        ]);

        $log = ActivityLog::where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($log->properties);
        $this->assertArrayHasKey('categories', $log->properties);
        $oldCats = $log->properties['categories']['old'];
        $newCats = $log->properties['categories']['new'];
        $this->assertCount(1, $oldCats);
        $this->assertEquals($this->cat1->name, $oldCats[0]['name']);
        $this->assertEquals(100, $oldCats[0]['percentage']);
        $this->assertCount(2, $newCats);
        $this->assertEquals($this->cat2->name, $newCats[0]['name']);
        $this->assertEquals(60, $newCats[0]['percentage']);
        $this->assertEquals($this->cat3->name, $newCats[1]['name']);
        $this->assertEquals(40, $newCats[1]['percentage']);
    }

    #[Group('activity_logs')]
    public function test_create_log_contains_categories(): void
    {
        $this->post('/transactions/store', [
            'account_id' => $this->savingsAccount->id,
            'amount' => 100,
            'credit' => false,
            'transaction_date' => '2025-01-01',
            'note' => 'Cat log test',
            'trans_buddy' => false,
            'recurring' => false,
            'categories' => [
                ['cat_data' => ['cat_id' => $this->cat2->id, 'name' => $this->cat2->name, 'hex_color' => $this->cat2->hex_color], 'percent' => 100],
            ],
        ]);

        $log = ActivityLog::where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($log->properties);
        $this->assertArrayHasKey('categories', $log->properties);
        $this->assertCount(1, $log->properties['categories']);
        $this->assertEquals($this->cat2->name, $log->properties['categories'][0]['name']);
        $this->assertEquals(100, $log->properties['categories'][0]['percentage']);
    }

    #[Group('activity_logs')]
    public function test_activity_log_page_renders(): void
    {
        ActivityLogService::log('test_event', 'Test entry for page');
        ActivityLogService::log('another_event', 'Another test entry');

        $response = $this->get(route('activity_log'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('ActivityLog')
                ->has('logs.data', 2)
                ->where('logs.data.0.description', 'Another test entry')
                ->where('logs.data.1.description', 'Test entry for page');
        });
    }

    #[Group('activity_logs')]
    public function test_activity_log_page_empty(): void
    {
        $response = $this->get(route('activity_log'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('ActivityLog')
                ->has('logs.data', 0);
        });
    }
}
