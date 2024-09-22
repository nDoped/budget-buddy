<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Database\Seeders\TestHarnessSeeder;
use \PHPUnit\Framework\Attributes\Group;
use Illuminate\Support\Facades\Log;
use Tests\Util;

use Inertia\Testing\AssertableInertia as Assert;

class TransactionTest extends TestCase
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
        // savingsTransaction1 is cat1 and cat3..
        // we need to get the actual percentages for the tests
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
    public function test_transaction_post()
    {
        $savingsTransactions = $this->savingsAccount->transactions;
        $this->assertCount(3, $savingsTransactions);
        $response = $this->post(
            '/transactions/store',
            [
                'account_id' => $this->savingsAccount->id,
                'amount' => 1000,
                'credit' => true,
                'note' => 'a newly posted transaction',
                'trans_buddy' => false,
                'recurring' => false,
                'categories' => [
                    [
                'cat_data' => [
                            'hex_color' => $this->cat1->hex_color,
                            'cat_id' => $this->cat1->id,
                            'name' => $this->cat1->name,
                        ],
                        'percent' => 50

                    ],
                    [
                        'cat_data' => [
                            'hex_color' => $this->cat2->hex_color,
                            'cat_id' => $this->cat2->id,
                            'name' => $this->cat2->name,
                        ],
                        'percent' => 50
                    ]
                ],
                'transaction_date' => '2021-09-01',
            ]
        );
        $response->assertStatus(302);

        $this->user->refresh();
        $this->assertCount(6, $this->user->transactions);
        $this->assertCount(2, $this->user->accounts);
        foreach ($this->user->transactions as $trans) {
            if ($trans->note === 'a newly posted transaction') {
                $this->assertEquals(1000 * 100, $trans->amount);
                $this->assertEquals($this->savingsAccount->id, $trans->account_id);
                //$this->assertEquals(1000, $user->transactions->pluck('amount'));
                $this->assertCount(2, $trans->categories);
                break;
            }
        }
        $this->savingsAccount->refresh();
        $savingsTransactions = $this->savingsAccount->transactions;
        $this->assertCount(4, $savingsTransactions);
    }

    #[Group('transactions')]
    public function test_transaction_post_with_new_category()
    {
        $newCatPercent = 32.73;
        $newCatName = "A WHOLE NEW CATEGORY!";
        $transNote = 'A transaction with a new category';
        $response = $this->post(
            '/transactions/store',
            [
                'account_id' => $this->savingsAccount->id,
                'amount' => 1000,
                'credit' => true,
                'note' => $transNote,
                'trans_buddy' => false,
                'recurring' => false,
                'categories' => [
                    [
                'cat_data' => [
                            'cat_id' => null,
                            'hex_color' => '#ff0000',
                            'name' => $newCatName,
                            'cat_type_id' => $this->catType1->id,
                        ],
                        'percent' => $newCatPercent

                    ],
                    [
                        'cat_data' => [
                            'hex_color' => $this->cat2->hex_color,
                            'cat_id' => $this->cat2->id,
                            'name' => $this->cat2->name,
                        ],
                        'percent' => 100 - $newCatPercent
                    ]
                ],
                'transaction_date' => '2021-09-01',
            ]
        );
        $response->assertStatus(302);
        $this->user->refresh();

        $this->assertCount(6, $this->user->transactions);
        foreach ($this->user->transactions as $trans) {
            if ($trans->note === $transNote) {
                $this->assertEquals(1000 * 100, $trans->amount);
                $this->assertEquals($this->savingsAccount->id, $trans->account_id);
                $categories = $trans->categories;
                $this->assertEquals(2, count($categories));
                foreach ($categories as $cat) {
                    if ($cat->id === $this->cat2->id) {
                        $this->assertEquals(round(100 - $newCatPercent, 2), round($cat->pivot->percentage / 100, 2));
                    } else {
                        $this->assertEquals($newCatPercent, $cat->pivot->percentage / 100);
                        $this->assertEquals($newCatName, $cat->name);
                    }
                }
                break;
            }
        }
    }

    #[Group('transactions')]
    public function test_transaction_post_with_invalid_percentage()
    {
        $firstCatPercent = 12.01;
        $data = [
            'account_id' => $this->savingsAccount->id,
            'amount' => 1000,
            'credit' => true,
            'note' => 'a newly posted transaction',
            'trans_buddy' => false,
            'recurring' => false,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat1->hex_color,
                        'cat_id' => $this->cat1->id,
                        'name' => $this->cat1->name,
                    ],
                    'percent' => $firstCatPercent

                ],
                [
                    'cat_data' => [
                        'hex_color' => $this->cat2->hex_color,
                        'cat_id' => $this->cat2->id,
                        'name' => $this->cat2->name,
                    ],
                    'percent' => 50
                ]
            ],
            'transaction_date' => '2021-09-01',
        ];
        $this->withoutExceptionHandling();
        try {
            $this->post(
                '/transactions/store',
                $data
            );
        } catch (\Exception $e) {
            $this->assertEquals('Category percentages do not sum to 100', $e->getMessage());
        }
    }

    #[Group('transactions')]
    public function test_transaction_post_recurring_biweekly()
    {
        $initial_trans_cnt = $this->user->transactions->count();
        $this->post(
            '/transactions/store',
            [
                'account_id' => $this->savingsAccount->id,
                'amount' => 1000,
                'credit' => false,
                'note' => 'parent trans with biweekly recurring',
                'trans_buddy' => false,
                'recurring' => true,
                'frequency' => 'biweekly',
                'recurring_end_date' => '2024-12-31',
                'categories' => [
                    [
                        'cat_data' => [
                            'hex_color' => $this->cat1->hex_color,
                            'cat_id' => $this->cat1->id,
                            'name' => $this->cat1->name,
                        ],
                        'percent' => 50

                    ],
                    [
                        'cat_data' => [
                            'hex_color' => $this->cat2->hex_color,
                            'cat_id' => $this->cat2->id,
                            'name' => $this->cat2->name,
                        ],
                        'percent' => 50
                    ]
                ],
                'transaction_date' => '2024-01-01',
            ]
        );
        $this->assertEquals($initial_trans_cnt + 27, $this->user->transactions()->count());

        $parent = Transaction::where('note', 'parent trans with biweekly recurring')
            ->where('transaction_date', '2024-01-01')
            ->first();
        $expectedCats = [
            $this->cat1->id => 50,
            $this->cat2->id => 50
        ];
        $expectedChildCnt = 26;
        $expectedChildCatCnt = 2;
        $this->_checkChildrenAfterPost(
            $parent,
            $expectedCats,
            $expectedChildCnt,
            $expectedChildCatCnt,
            $this->savingsAccount
        );
    }

    #[Group('transactions')]
    public function test_transaction_post_recurring_monthly()
    {
        $initial_trans_cnt = $this->user->transactions->count();
        $this->post(
            '/transactions/store',
            [
                'account_id' => $this->savingsAccount->id,
                'amount' => 1000,
                'credit' => false,
                'note' => 'parent trans monthly recurring',
                'trans_buddy' => false,
                'recurring' => true,
                'frequency' => 'monthly',
                'recurring_end_date' => '2023-12-31',
                'categories' => [
                    [
                        'cat_data' => [
                            'hex_color' => $this->cat1->hex_color,
                            'cat_id' => $this->cat1->id,
                            'name' => $this->cat1->name,
                        ],
                        'percent' => 50

                    ],
                    [
                        'cat_data' => [
                            'hex_color' => $this->cat2->hex_color,
                            'cat_id' => $this->cat2->id,
                            'name' => $this->cat2->name,
                        ],
                        'percent' => 50
                    ]
                ],
                'transaction_date' => '2023-01-01',
            ]
        );
        $this->assertEquals($initial_trans_cnt + 12, $this->user->transactions()->count());
        $parent = Transaction::where('note', 'parent trans monthly recurring')
            ->where('transaction_date', '2023-01-01')
            ->first();
        $expectedCats = [
            $this->cat1->id => 50,
            $this->cat2->id => 50
        ];
        $expectedChildCnt = 11;
        $expectedChildCatCnt = 2;
        $this->_checkChildrenAfterPost(
            $parent,
            $expectedCats,
            $expectedChildCnt,
            $expectedChildCatCnt,
            $this->savingsAccount
        );
    }

    #[Group('transactions')]
    public function test_transaction_post_recurring_with_buddies()
    {
        $initial_trans_cnt = $this->user->transactions->count();
        $firstCatPercent = 42.37;
        $this->post(
            '/transactions/store',
            [
                'account_id' => $this->savingsAccount->id,
                'amount' => 1000,
                'credit' => false,
                'note' => 'parent trans with recurring buddies',
                'trans_buddy' => true,
                'trans_buddy_account' => $this->creditCardAccount->id,
                'trans_buddy_note' => 'buddy note',
                'recurring' => true,
                'frequency' => 'monthly',
                'recurring_end_date' => '2025-08-31',
                'categories' => [
                    [
                        'cat_data' => [
                            'hex_color' => $this->cat1->hex_color,
                            'cat_id' => $this->cat1->id,
                            'name' => $this->cat1->name,
                        ],
                        'percent' => $firstCatPercent

                    ],
                    [
                        'cat_data' => [
                            'hex_color' => $this->cat2->hex_color,
                            'cat_id' => $this->cat2->id,
                            'name' => $this->cat2->name,
                        ],
                        'percent' => 100 - $firstCatPercent
                    ]
                ],
                'transaction_date' => '2024-09-11',
            ]
        );
        $this->assertEquals($initial_trans_cnt + 12 + 12, $this->user->transactions()->count());
        $parent = Transaction::where('note', 'parent trans with recurring buddies')
            ->where('transaction_date', '2024-09-11')
            ->first();
        $expectedCats = [
            $this->cat1->id => $firstCatPercent,
            $this->cat2->id => 100 - $firstCatPercent
        ];
        $expectedChildCnt = 11;
        $expectedChildCatCnt = 2;
        $this->_checkChildrenAfterPost(
            $parent,
            $expectedCats,
            $expectedChildCnt,
            $expectedChildCatCnt,
            $this->savingsAccount,
            true,
            $this->creditCardAccount,
            $expectedChildCatCnt
        );
    }

    #[Group('transactions')]
    public function test_transaction_patch()
    {
        $this->assertEquals(420, $this->savingsTransaction0->amount / 100);
        $this->assertEquals(true, $this->savingsTransaction0->credit);
        $updateData = [
            'account_id' => $this->creditCardAccount->id,
            'transaction_date' => '2002-01-01',
            'bank_identifier' => 'an updated bank identifier',
            'amount' => 1000,
            'credit' => true,
            'note' => 'an updated note',
            'categories' => []

        ];
        $response = $this->patch(
            '/transactions/update/' . $this->savingsTransaction0->id,
            $updateData
        );
        $response->assertStatus(302);
        $this->savingsTransaction0->refresh();
        $this->assertEquals($updateData['account_id'], $this->creditCardAccount->id);
        $this->assertEquals($updateData['transaction_date'], $this->savingsTransaction0->transaction_date);
        $this->assertEquals($updateData['bank_identifier'], $this->savingsTransaction0->bank_identifier);
        $this->assertEquals($updateData['amount'] * 100, $this->savingsTransaction0->amount);
        $this->assertEquals($updateData['credit'], $this->savingsTransaction0->credit);
        $this->assertEquals($updateData['note'], $this->savingsTransaction0->note);
        $this->assertCount(0, $this->savingsTransaction0->categories);
    }

    #[Group('transactions')]
    public function test_transaction_patch_update_categories()
    {
        $this->assertEquals(420, $this->savingsTransaction0->amount / 100);
        $this->assertEquals(true, $this->savingsTransaction0->credit);
        $firstCatPercent = 15.92;
        $updateData = [
            'account_id' => $this->creditCardAccount->id,
            'transaction_date' => '2002-01-01',
            'bank_identifier' => 'an updated bank identifier',
            'amount' => 1000,
            'credit' => true,
            'note' => 'an updated note',
            'edit_child_transactions' => true,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => $firstCatPercent

                ],
                [
                    'cat_data' => [
                        'hex_color' => "#aabbcc",
                        'name' => 'YAC!'
                    ],
                    'percent' => 100 - $firstCatPercent
                ]
            ],
        ];
        $savingsTrans1Cats = $this->savingsTransaction1->categories;
        $this->assertCount(2, $savingsTrans1Cats);
        foreach ($savingsTrans1Cats as $cat) {
            if ($cat->id === $this->cat3->id) {
                $this->assertEquals($this->actualCatPercentage2, $cat->pivot->percentage / 10000);
            } else {
                $this->assertEquals($this->cat1->id, $cat->id);
                $this->assertEquals($this->actualCatPercentage1, $cat->pivot->percentage / 10000);
            }
        }

        $response = $this->patch(
            '/transactions/update/' . $this->savingsTransaction0->id,
            $updateData
        );
        $response->assertStatus(302);
        $this->savingsTransaction0->refresh();
        $this->assertEquals($updateData['account_id'], $this->creditCardAccount->id);
        $this->assertEquals($updateData['transaction_date'], $this->savingsTransaction0->transaction_date);
        $this->assertEquals($updateData['bank_identifier'], $this->savingsTransaction0->bank_identifier);
        $this->assertEquals($updateData['amount'] * 100, $this->savingsTransaction0->amount);
        $this->assertEquals($updateData['credit'], $this->savingsTransaction0->credit);
        $this->assertEquals($updateData['note'], $this->savingsTransaction0->note);
        $this->assertCount(2, $this->savingsTransaction0->categories);
        foreach ($this->savingsTransaction0->categories as $cat) {
            if ($cat->id === $this->cat3->id) {
                $this->assertEquals($firstCatPercent, $cat->pivot->percentage / 100);
            } else {
                $this->assertEquals(100 - $firstCatPercent, $cat->pivot->percentage / 100);
                $this->assertEquals(
                    $updateData['categories'][1]['cat_data']['name'],
                    $cat->name
                );
            }
        }

        $this->savingsTransaction1->refresh();
        $savingsTrans1Cats = $this->savingsTransaction1->categories;
        $this->assertCount(2, $savingsTrans1Cats);
        foreach ($savingsTrans1Cats as $cat) {
            if ($cat->id === $this->cat3->id) {
                $this->assertEquals($this->actualCatPercentage2, $cat->pivot->percentage / 10000);
            } else {
                $this->assertEquals($this->cat1->id, $cat->id);
                $this->assertEquals($this->actualCatPercentage1, $cat->pivot->percentage / 10000);
            }
        }
    }

    #[Group('transactions')]
    public function test_transaction_patch_update_categories_with_invalid_id()
    {
        $firstCatPercent = 15.92;
        $updateData = [
            'account_id' => $this->creditCardAccount->id,
            'transaction_date' => '2002-01-01',
            'bank_identifier' => 'an updated bank identifier',
            'amount' => 1000,
            'credit' => true,
            'note' => 'an updated note',
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => $firstCatPercent

                ],
                [
                    'cat_data' => [
                        'cat_id' => -99,
                        'hex_color' => "#aabbcc",
                        'name' => 'YAC!'
                    ],
                    'percent' => 100 - $firstCatPercent
                ]
            ],
        ];

        $this->withoutExceptionHandling();
        try {
            $this->patch(
                '/transactions/update/' . $this->savingsTransaction0->id,
                $updateData
            );
        } catch (\Exception $e) {
            $this->assertEquals('Invalid category id', $e->getMessage());
        }
    }

    #[Group('transactions')]
    public function test_transaction_patch_update_recurring()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $this->assertEquals(1, $this->user->transactions()->count());
        $endDate = new \DateTime($this->savingsTransaction0->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        $this->savingsTransaction0->createRecurringSeries(
            $endDate,
            'biweekly'
        );
        $this->assertEquals(27, $this->user->transactions()->count());

        $this->assertEquals(420, $this->savingsTransaction0->amount / 100);
        $this->assertEquals(true, $this->savingsTransaction0->credit);
        $firstCatPercent = 15.92;
        $updateData = [
            'account_id' => $this->creditCardAccount->id,
            'transaction_date' => '2002-01-01',
            'bank_identifier' => 'an updated bank identifier',
            'amount' => 1000,
            'credit' => true,
            'note' => 'an updated note',
            'edit_child_transactions' => true,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => $firstCatPercent

                ],
                [
                    'cat_data' => [
                        'hex_color' => "#aabbcc",
                        'name' => 'YAC!'
                    ],
                    'percent' => 100 - $firstCatPercent
                ]
            ],
        ];
        $response = $this->patch(
            '/transactions/update/' . $this->savingsTransaction0->id,
            $updateData
        );
        $response->assertStatus(302);
        $this->savingsTransaction0->refresh();
        $this->assertEquals($updateData['account_id'], $this->creditCardAccount->id);
        $this->assertEquals($updateData['transaction_date'], $this->savingsTransaction0->transaction_date);
        $this->assertEquals($updateData['bank_identifier'], $this->savingsTransaction0->bank_identifier);
        $this->assertEquals($updateData['amount'] * 100, $this->savingsTransaction0->amount);
        $this->assertEquals($updateData['credit'], $this->savingsTransaction0->credit);
        $this->assertEquals($updateData['note'], $this->savingsTransaction0->note);
        $this->assertCount(2, $this->savingsTransaction0->categories);
        foreach ($this->savingsTransaction0->categories as $cat) {
            if ($cat->id === $this->cat3->id) {
                $this->assertEquals($firstCatPercent, $cat->pivot->percentage / 100);
            } else {
                $this->assertEquals(100 - $firstCatPercent, $cat->pivot->percentage / 100);
                $this->assertEquals(
                    $updateData['categories'][1]['cat_data']['name'],
                    $cat->name
                );
            }
        }
    }

    #[Group('transactions')]
    public function test_transaction_patch_update_recurring_with_buddies()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $this->assertEquals(1, $this->user->transactions()->count());
        $endDate = new \DateTime($this->savingsTransaction0->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        $this->savingsTransaction0->createBuddyTransaction(
            $this->creditCardAccount,
            'buddy note'
        );
        $this->savingsTransaction0->createRecurringSeries(
            $endDate,
            'biweekly'
        );
        $this->assertEquals(54, $this->user->transactions()->count());

        $this->assertEquals(420, $this->savingsTransaction0->amount / 100);
        $this->assertEquals(true, $this->savingsTransaction0->credit);
        $firstCatPercent = 15.92;
        $updateData = [
            'account_id' => $this->creditCardAccount->id,
            'transaction_date' => '2002-01-01',
            'bank_identifier' => 'an updated bank identifier',
            'amount' => 1000,
            'credit' => true,
            'note' => 'an updated note',
            'edit_child_transactions' => true,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => $firstCatPercent

                ],
                [
                    'cat_data' => [
                        'hex_color' => "#aabbcc",
                        'name' => 'YAC!'
                    ],
                    'percent' => 100 - $firstCatPercent
                ]
            ],
        ];
        // original savingsTransaction0 category
        $originalCategoryData = [
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat1->hex_color,
                        'cat_id' => $this->cat1->id,
                        'name' => $this->cat1->name,
                    ],
                    'percent' => 100

                ],
            ],
        ];
        // sanity check before making the request
        $this->_checkCategories($this->savingsTransaction0, $originalCategoryData);
        $buddy = $this->savingsTransaction0->buddyTransaction();
        $this->_checkCategories($buddy, $originalCategoryData);
        foreach ($this->savingsTransaction0->children() as $child) {
            $this->_checkCategories($child, $originalCategoryData);
            $childBuddy = $child->buddyTransaction();
            $this->assertCount(count($originalCategoryData['categories']), $childBuddy->categories);
            $this->_checkCategories($childBuddy, $originalCategoryData);
        }

        $response = $this->patch(
            '/transactions/update/' . $this->savingsTransaction0->id,
            $updateData
        );
        $response->assertStatus(302);

        $this->savingsTransaction0->refresh();
        $this->assertEquals($updateData['account_id'], $this->creditCardAccount->id);
        $this->assertEquals($updateData['transaction_date'], $this->savingsTransaction0->transaction_date);
        $this->assertEquals($updateData['bank_identifier'], $this->savingsTransaction0->bank_identifier);
        $this->assertEquals($updateData['amount'] * 100, $this->savingsTransaction0->amount);
        $this->assertEquals($updateData['credit'], $this->savingsTransaction0->credit);
        $this->assertEquals($updateData['note'], $this->savingsTransaction0->note);
        $this->_checkCategories($this->savingsTransaction0, $updateData);
        $buddy = $this->savingsTransaction0->buddyTransaction();
        $this->_checkCategories($buddy, $originalCategoryData);
        foreach ($this->savingsTransaction0->children() as $child) {
            $this->assertEquals($updateData['amount'] * 100, $child->amount);
            $this->_checkCategories($child, $updateData);

            $childBuddy = $child->buddyTransaction();
            $this->assertEquals($updateData['amount'] * 100, $childBuddy->amount);
            $this->assertEquals(! $updateData['credit'], $childBuddy->credit);
            $this->_checkCategories($childBuddy, $originalCategoryData);
        }
    }

    #[Group('transactions')]
    public function test_transaction_patch_child_update()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $firstCatPercent = 15.92;
        // ensure the new date is two weeks after the parent
        $d = new \DateTime($this->savingsTransaction0->transaction_date);
        $newTransDate = $d->add(new \DateInterval('P14D'))->format('Y-m-d');
        $updateData = [
            'account_id' => $this->creditCardAccount->id,
            'transaction_date' => $newTransDate,
            'bank_identifier' => 'an updated bank identifier',
            'amount' => 47.89,
            'credit' => true,
            'note' => 'an updated note',
            'edit_child_transactions' => true,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => $firstCatPercent

                ],
                [
                    'cat_data' => [
                        'hex_color' => "#aabbcc",
                        'name' => 'YAC!'
                    ],
                    'percent' => 100 - $firstCatPercent
                ]
            ],
        ];
        // original savingsTransaction0 category
        $originalData = [
            'account_id' => $this->savingsTransaction0->account_id,
            'transaction_date' => $this->savingsTransaction0->transaction_date,
            'bank_identifier' => $this->savingsTransaction0->bank_identifier,
            'amount' => $this->savingsTransaction0->amount,
            'credit' => $this->savingsTransaction0->credit,
            'note' => $this->savingsTransaction0->note,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat1->hex_color,
                        'cat_id' => $this->cat1->id,
                        'name' => $this->cat1->name,
                    ],
                    'percent' => 100

                ],
            ],
        ];
        $this->assertEquals(1, $this->user->transactions()->count());
        $endDate = new \DateTime($this->savingsTransaction0->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        $this->savingsTransaction0->createBuddyTransaction(
            $this->creditCardAccount,
            'buddy note'
        );
        $this->savingsTransaction0->createRecurringSeries(
            $endDate,
            'biweekly'
        );
        $this->assertEquals(54, $this->user->transactions()->count());

        $this->assertEquals(420, $this->savingsTransaction0->amount / 100);
        $this->assertEquals(true, $this->savingsTransaction0->credit);
        // sanity check before making the request
        $this->assertEquals($originalData['account_id'], $this->savingsTransaction0->account_id);
        $this->assertEquals($originalData['transaction_date'], $this->savingsTransaction0->transaction_date);
        $this->assertEquals($originalData['bank_identifier'], $this->savingsTransaction0->bank_identifier);
        $this->assertEquals($originalData['amount'], $this->savingsTransaction0->amount);
        $this->assertEquals($originalData['credit'], $this->savingsTransaction0->credit);
        $this->assertEquals($originalData['note'], $this->savingsTransaction0->note);
        $this->_checkCategories($this->savingsTransaction0, $originalData);
        $buddy = $this->savingsTransaction0->buddyTransaction();
        $this->_checkCategories($buddy, $originalData);
        foreach ($this->savingsTransaction0->children() as $child) {
            $this->_checkCategories($child, $originalData);
            $childBuddy = $child->buddyTransaction();
            $this->assertCount(count($originalData['categories']), $childBuddy->categories);
            $this->_checkCategories($childBuddy, $originalData);
        }

        $child = $this->savingsTransaction0->children()->first();
        $response = $this->patch(
            '/transactions/update/' . $child->id,
            $updateData
        );
        $response->assertStatus(302);

        // parent trans should be untouched
        $this->savingsTransaction0->refresh();
        $this->assertEquals($originalData['account_id'], $this->savingsTransaction0->account_id);
        $this->assertEquals($originalData['transaction_date'], $this->savingsTransaction0->transaction_date);
        $this->assertEquals($originalData['bank_identifier'], $this->savingsTransaction0->bank_identifier);
        $this->assertEquals($originalData['amount'], $this->savingsTransaction0->amount);
        $this->assertEquals($originalData['credit'], $this->savingsTransaction0->credit);
        $this->assertEquals($originalData['note'], $this->savingsTransaction0->note);
        $this->_checkCategories($this->savingsTransaction0, $originalData);
        $buddy = $this->savingsTransaction0->buddyTransaction();
        $this->_checkCategories($buddy, $originalData);

        // first child on should be updated
        $this->_checkChildrenAfterPatch($this->savingsTransaction0->children(), $updateData);
    }

    #[Group('transactions')]
    public function test_transaction_patch_image_update()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $base64Meta = 'data:image/png;base64,';
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $base64 = $base64Meta . $base64Image;
        $trans = new Transaction();
        $imgName = 'test_image';
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => '2024-06-10',
            'amount' => 100,
            'credit' => false,
            'description' => 'test',
            'new_images' => [
                [
                    'base64' => $base64,
                    'name' => $imgName
                ]
            ],
            'is_credit' => false,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => 100

                ],
            ]
        ];
        $createdTrans = $trans->create($data);
        $targetTrans = $createdTrans->first();

        $this->assertCount(1, $targetTrans->transactionImages);
        $firstImg = $targetTrans->transactionImages->first();
        $img = Storage::disk('local')->get($firstImg->path);
        $this->assertEquals($base64Image, base64_encode($img));

        $updatedBase64Image = 'iVBORw0KGgoAAAANSUhEUgAAAQAAAAEACAIAAADTED8xAAADMElEQVR4nOzVwQnAIBQFQYXff81RUkQCOyDj1YOPnbXWPmeTRef+/3O/OyBjzh3CD95BfqICMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMO0TAAD//2Anhf4QtqobAAAAAElFTkSuQmCC';
        $newImgName = "another image";
        $updateData = [
            'account_id' => $this->creditCardAccount->id,
            'transaction_date' => '2024-06-10',
            'bank_identifier' => 'an updated bank identifier',
            'amount' => 47.89,
            'credit' => true,
            'note' => 'an updated note',
            'edit_child_transactions' => true,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => 50

                ],
                [
                    'cat_data' => [
                        'hex_color' => "#aabbcc",
                        'name' => 'YAC!'
                    ],
                    'percent' => 50
                ]
            ],
            'new_images' => [
                [
                    'base64' => $base64Meta . $updatedBase64Image,
                    'name' => $newImgName
                ]
            ],
        ];
        $existingImg = $targetTrans->transactionImages->first();
        $updatedName = 'this is the first img, now updated';
        $existing = [
            'path' => $existingImg->path,
            'name' => $updatedName,
            'id' => $existingImg->id
        ];
        $updateData['existing_images'] = [ $existing ];


        $this->assertEquals(1, $this->user->transactions()->count());
        $response = $this->patch(
            '/transactions/update/' . $targetTrans->id,
            $updateData
        );
        $response->assertStatus(302);
        $targetTrans = Transaction::find($targetTrans->id);
        $this->assertCount(2, $targetTrans->transactionImages);
        $expectedImages = [
            $base64Image,
            $updatedBase64Image
        ];
        foreach ($targetTrans->transactionImages as $img) {
            $this->assertContains(base64_encode(Storage::disk('local')->get($img->path)), $expectedImages);
            if ($img->id === $existing['id']) {
                $this->assertEquals($updatedName, $img->name);
            } else {
                $this->assertEquals($newImgName, $img->name);
            }
            $img->delete();
        }
    }

    #[Group('transactions')]
    public function test_transaction_patch_image_delete()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $base64Meta = 'data:image/png;base64,';
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $base64 = $base64Meta . $base64Image;
        $base64Image_1 = 'iVBORw0KGgoAAAANSUhEUgAAAQAAAAEACAIAAADTED8xAAADMElEQVR4nOzVwQnAIBQFQYXff81RUkQCOyDj1YOPnbXWPmeTRef+/3O/OyBjzh3CD95BfqICMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMK0CMO0TAAD//2Anhf4QtqobAAAAAElFTkSuQmCC';
        $base64_1 = $base64Meta . $base64Image_1;
        $trans = new Transaction();
        $imgName = 'test_image';
        $imgName1 = 'test_image1';
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => '2024-06-10',
            'amount' => 100,
            'credit' => false,
            'description' => 'test',
            'new_images' => [
                [
                    'base64' => $base64,
                    'name'=> $imgName
                ],
                [
                    'base64' => $base64_1,
                    'name'=> $imgName1
                ],
            ],
            'is_credit' => false,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => 100

                ],
            ]
        ];
        $createdTrans = $trans->create($data);
        $targetTrans = $createdTrans->first();

        $this->assertCount(2, $targetTrans->transactionImages);
        $keepImg = $targetTrans->transactionImages->first();
        $file = Storage::disk('local')->get($keepImg->path);
        $this->assertEquals($base64Image, base64_encode($file));
        $deleteImg = $targetTrans->transactionImages->last();
        $file1 = Storage::disk('local')->get($deleteImg->path);
        $this->assertEquals($base64Image_1, base64_encode($file1));

        $updateData = [
            'account_id' => $this->creditCardAccount->id,
            'transaction_date' => '2024-06-10',
            'bank_identifier' => 'an updated bank identifier',
            'amount' => 47.89,
            'credit' => true,
            'note' => 'an updated note',
            'edit_child_transactions' => true,
            'existing_images' => [ $keepImg->toArray() ],
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => 50

                ],
                [
                    'cat_data' => [
                        'hex_color' => "#aabbcc",
                        'name' => 'YAC!'
                    ],
                    'percent' => 50
                ]
            ],
        ];
        $response = $this->patch(
            '/transactions/update/' . $targetTrans->id,
            $updateData
        );
        $response->assertStatus(302);
        $targetTrans = Transaction::find($targetTrans->id);
        $this->assertCount(1, $targetTrans->transactionImages);
        $img = $targetTrans->transactionImages->first();
        $this->assertEquals($keepImg->path, $img->path);
        $this->assertNotNull(Storage::disk('local')->get($keepImg->path));
        $this->assertNull(Storage::disk('local')->get($deleteImg->path));
        $keepImg->delete();
    }

    #[Group('transactions')]
    public function test_transaction_patch_child_update_with_buddies()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $firstCatPercent = 15.92;
        // ensure the new date is two weeks after the parent
        $d = new \DateTime($this->savingsTransaction0->transaction_date);
        $newTransDate = $d->add(new \DateInterval('P14D'))->format('Y-m-d');
        $updateData = [
            'account_id' => $this->creditCardAccount->id,
            'transaction_date' => $newTransDate,
            'bank_identifier' => 'an updated bank identifier',
            'amount' => 47.89,
            'credit' => true,
            'note' => 'an updated note',
            'edit_child_transactions' => true,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat3->hex_color,
                        'cat_id' => $this->cat3->id,
                        'name' => $this->cat3->name,
                    ],
                    'percent' => $firstCatPercent

                ],
                [
                    'cat_data' => [
                        'hex_color' => "#aabbcc",
                        'name' => 'YAC!'
                    ],
                    'percent' => 100 - $firstCatPercent
                ]
            ],
        ];
        // original savingsTransaction0 category
        $originalData = [
            'account_id' => $this->savingsTransaction0->account_id,
            'transaction_date' => $this->savingsTransaction0->transaction_date,
            'bank_identifier' => $this->savingsTransaction0->bank_identifier,
            'amount' => $this->savingsTransaction0->amount,
            'credit' => $this->savingsTransaction0->credit,
            'note' => $this->savingsTransaction0->note,
            'categories' => [
                [
                    'cat_data' => [
                        'hex_color' => $this->cat1->hex_color,
                        'cat_id' => $this->cat1->id,
                        'name' => $this->cat1->name,
                    ],
                    'percent' => 100

                ],
            ],
        ];
        $this->assertEquals(1, $this->user->transactions()->count());
        $endDate = new \DateTime($this->savingsTransaction0->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        $this->savingsTransaction0->createBuddyTransaction(
            $this->creditCardAccount,
            'buddy note'
        );
        $this->savingsTransaction0->createRecurringSeries(
            $endDate,
            'biweekly'
        );
        $this->assertEquals(54, $this->user->transactions()->count());

        $this->assertEquals(420, $this->savingsTransaction0->amount / 100);
        $this->assertEquals(true, $this->savingsTransaction0->credit);
        // sanity check before making the request
        $this->assertEquals($originalData['account_id'], $this->savingsTransaction0->account_id);
        $this->assertEquals($originalData['transaction_date'], $this->savingsTransaction0->transaction_date);
        $this->assertEquals($originalData['bank_identifier'], $this->savingsTransaction0->bank_identifier);
        $this->assertEquals($originalData['amount'], $this->savingsTransaction0->amount);
        $this->assertEquals($originalData['credit'], $this->savingsTransaction0->credit);
        $this->assertEquals($originalData['note'], $this->savingsTransaction0->note);
        $this->_checkCategories($this->savingsTransaction0, $originalData);
        $buddy = $this->savingsTransaction0->buddyTransaction();
        $this->_checkCategories($buddy, $originalData);
        foreach ($this->savingsTransaction0->children() as $child) {
            $this->_checkCategories($child, $originalData);
            $childBuddy = $child->buddyTransaction();
            $this->assertCount(count($originalData['categories']), $childBuddy->categories);
            $this->_checkCategories($childBuddy, $originalData);
        }

        $child = $this->savingsTransaction0->children()->first();
        $response = $this->patch(
            '/transactions/update/' . $child->id,
            $updateData
        );
        $response->assertStatus(302);

        // parent trans should be untouched
        $this->savingsTransaction0->refresh();
        $this->assertEquals($originalData['account_id'], $this->savingsTransaction0->account_id);
        $this->assertEquals($originalData['transaction_date'], $this->savingsTransaction0->transaction_date);
        $this->assertEquals($originalData['bank_identifier'], $this->savingsTransaction0->bank_identifier);
        $this->assertEquals($originalData['amount'], $this->savingsTransaction0->amount);
        $this->assertEquals($originalData['credit'], $this->savingsTransaction0->credit);
        $this->assertEquals($originalData['note'], $this->savingsTransaction0->note);
        $this->_checkCategories($this->savingsTransaction0, $originalData);
        $buddy = $this->savingsTransaction0->buddyTransaction();
        $this->_checkCategories($buddy, $originalData);

        // first child on should be updated
        $this->_checkChildrenAfterPatch($this->savingsTransaction0->children(), $updateData, $originalData);
    }
    private function _checkChildrenAfterPatch(Collection $children, $expectedUpdatedData, $expectedOriginalData = [])
    {
        foreach ($children as $child) {
            $this->assertEquals($expectedUpdatedData['amount'] * 100, $child->amount);
            $this->assertEquals($expectedUpdatedData['account_id'], $child->account_id);
            $this->assertGreaterThanOrEqual($expectedUpdatedData['transaction_date'], $child->transaction_date);
            $this->assertEquals($expectedUpdatedData['credit'], $child->credit);
            $this->assertEquals($expectedUpdatedData['note'], $child->note);
            $this->_checkCategories($child, $expectedUpdatedData);
            if ($expectedOriginalData) {
                $childBuddy = $child->buddyTransaction();
                $this->assertEquals($expectedUpdatedData['amount'] * 100, $childBuddy->amount);
                $this->assertEquals(! $expectedUpdatedData['credit'], $childBuddy->credit);
                $this->_checkCategories($childBuddy, $expectedOriginalData);
            }
        }
    }
    private function _checkCategories(Transaction $transaction, $updateData)
    {
        $this->assertCount(count($updateData['categories']), $transaction->categories);
        foreach ($transaction->categories as $cat) {
            $firstCatPercent = $updateData['categories'][0]['percent'];
            if ($cat->id === $updateData['categories'][0]['cat_data']['cat_id']) {
                $this->assertEquals($firstCatPercent, $cat->pivot->percentage / 100);
            } else {
                $this->assertEquals(100 - $firstCatPercent, $cat->pivot->percentage / 100);
                $this->assertEquals(
                    $updateData['categories'][1]['cat_data']['name'],
                    $cat->name
                );
            }
        }
    }

    #[Group('transactions')]
    public function test_transaction_get_two_transactions_in_range()
    {
        $this->actingAs($user = User::factory()->create());
        $account = Account::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($account)->create();
        $transaction->transaction_date = '2024-06-15';
        $transaction->save();
        $transaction2 = Transaction::factory()->for($account)->create();
        $transaction2->transaction_date = '2024-06-14';
        $transaction2->save();
        $params = [
            'start' => '2024-06-01',
            'end' => '2024-06-30',
        ];
        $query = http_build_query($params);
        $response = $this->get('/transactions?' . $query);

        $response->assertInertia(fn (Assert $page) => $page
                 ->component('Transactions')
                 ->has('data.transactions_in_range', 2,  fn (Assert $page) => $page
                    ->where('amount', strval($transaction->amount / 100))
                    ->where('amount_raw', $transaction->amount)
                    ->etc()
                 )
                 ->has('data.transactions_in_range.1', fn (Assert $page) => $page
                    ->where('amount', strval($transaction2->amount / 100))
                    ->where('amount_raw', $transaction2->amount)
                    ->etc()
                 )
        );
    }

    #[Group('transactions')]
    public function test_transaction_get_one_transaction_in_range()
    {
        $this->actingAs($user = User::factory()->create());
        $account = Account::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($account)->create();
        $transaction->transaction_date = '2024-06-15';
        $transaction->save();
        $transaction2 = Transaction::factory()->for($account)->create();
        $transaction2->transaction_date = '2024-05-14';
        $transaction2->save();
        $params = [
            'start' => '2024-06-01',
            'end' => '2024-06-30',
        ];
        $query = http_build_query($params);
        $this->get(
            '/transactions?' . $query
        )->assertInertia(fn (Assert $page) => $page
                 ->component('Transactions')
                 ->has('data.transactions_in_range', 1,  fn (Assert $page) => $page
                    ->where('amount', strval($transaction->amount / 100))
                    ->where('amount_raw', $transaction->amount)
                    ->etc()
                 )
        );
    }

    #[Group('transactions')]
    public function test_transaction_get_one_transaction_with_image()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $base64Meta = 'data:image/png;base64,';
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $base64 = $base64Meta . $base64Image;
        $trans = new Transaction();
        $imgName = 'test image';
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => '2024-06-10',
            'amount' => 100,
            'credit' => false,
            'description' => 'test',
            'new_images' => [
                [
                    'base64' => $base64,
                    'name' => $imgName
                ]
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
        $expectedTrans = $createdTrans->first();
        $this->assertCount(1, $expectedTrans->transactionImages);
        $transImg = $expectedTrans->transactionImages->first();
        $imgPath = $transImg->path;
        $img = Storage::disk('local')->get($imgPath);
        $this->assertEquals($base64Image, base64_encode($img));
        $this->assertEquals($imgName, $transImg->name);
        $params = [
            'start' => '2024-06-01',
            'end' => '2024-06-30',
        ];
        $query = http_build_query($params);
        $this->get(
            '/transactions?' . $query
        )->assertInertia(
            function (Assert $page) use ($imgPath) {
                $data = $page->toArray()['props']['data'];
                $transInRange = $data['transactions_in_range'];
                $this->assertCount(1, $transInRange);
                $this->assertCount(1, $transInRange[0]['existing_images']);
                $this->assertEquals($imgPath, $transInRange[0]['existing_images'][0]['path']);
                return $page->component('Transactions');
            }
        );
        $transImg->delete();
    }

    #[Group('transactions')]
    public function test_transaction_get_with_account_filter()
    {
        $this->savingsTransaction0->transaction_date = '2024-06-15';
        $this->savingsTransaction0->save();
        $this->creditTransaction2->transaction_date = '2024-06-17';
        $this->creditTransaction2->save();
        $params = [
            'start' => '2024-06-01',
            'end' => '2024-06-30',
            'filter_accounts' => [
                $this->savingsAccount->id
            ]
        ];
        $query = http_build_query($params);
        $this->get('/transactions?' . $query)->assertInertia(
            function (Assert $page) {
                $data = $page->toArray()['props']['data'];
                $transactions_in_range = $data['transactions_in_range'];
                $this->assertCount(3, $transactions_in_range);
                $expected_transactions = [
                    $this->savingsTransaction0,
                    $this->savingsTransaction1,
                    $this->savingsTransaction2
                ];
                foreach ($transactions_in_range as $trans) {
                    $this->_checkTransaction($trans, $expected_transactions, $this->savingsAccount);
                }

                return $page->component('Transactions');
            }
        );
    }

    #[Group('transactions')]
    public function test_destroy()
    {
        $this->assertCount(5, $this->user->transactions);
        $response = $this->delete(route('transactions.destroy', [ 'id' => $this->savingsTransaction0->id ]));
        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(4, $this->user->transactions);
    }

    #[Group('transactions')]
    public function test_destroy_with_image()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $base64Meta = 'data:image/png;base64,';
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $base64 = $base64Meta . $base64Image;
        $trans = new Transaction();
        $imgName = 'test_image';
        $data = [
            'account_id' => $this->savingsAccount->id,
            'transaction_date' => '2024-06-10',
            'amount' => 100,
            'credit' => false,
            'description' => 'test',
            'new_images' => [
                [
                    'base64' => $base64,
                    'name' => $imgName
                ]
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
        $toDelete = $createdTrans->first();
        $transImg = $toDelete->transactionImages->first();
        $toDeleteImgPath = $transImg->path;
        $img = Storage::disk('local')->get($toDeleteImgPath);
        $this->assertEquals($base64Image, base64_encode($img));
        $response = $this->delete(route('transactions.destroy', [ 'id' => $toDelete->id ]));
        $response->assertStatus(302);
        $this->assertNull(Storage::disk('local')->get($toDeleteImgPath));
    }

    #[Group('transactions')]
    public function test_destroy_invalid_id()
    {
        $this->assertCount(5, $this->user->transactions);
        $response = $this->delete(route('transactions.destroy', [ 'id' => -99 ]));
        $response->assertStatus(302);
        $this->assertEquals(5, $this->user->transactions()->count());
    }

    #[Group('transactions')]
    public function test_destroy_buddy()
    {
        $buddyTrans =  $this->savingsTransaction2->createBuddyTransaction(
            $this->creditCardAccount,
            'SavingsTransaction2 buddy note',
        );
        $this->assertEquals(6, $this->user->transactions()->count());
        $response = $this->delete(
            route(
                'transactions.destroy',
                [
                    'id' => $buddyTrans,
                ]
            )
        );
        $this->assertEquals(4, $this->user->transactions()->count());
        $response->assertStatus(302);
    }

    #[Group('transactions')]
    public function test_destroy_parent()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $this->assertCount(1, $this->user->transactions);
        $this->creditTransaction2->createRecurringSeries();
        $this->assertEquals(13, $this->user->transactions()->count());
        foreach ($this->creditTransaction2->children() as $child) {
            $this->assertEquals($this->creditTransaction2->id, $child->parent_id);
        }
        $response = $this->delete(
            route(
                'transactions.destroy',
                [
                    'id' => $this->creditTransaction2->id,
                ]
            )
        );
        $this->assertNull(Transaction::find($this->creditTransaction2->id));
        $this->assertEquals(12, $this->user->transactions()->count());
        $newParent = Transaction::whereColumn('parent_id', 'id')->first();
        $this->assertEquals($newParent->parent_id, $newParent->id);
        $series = Transaction::where('parent_id', $newParent->id)->get();
        foreach ($series as $trans) {
            $this->assertEquals($newParent->id, $trans->parent_id);
        }
        $response->assertStatus(302);
    }

    #[Group('transactions')]
    public function test_destroy_parent_and_children()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $this->assertEquals(1, $this->user->transactions()->count());
        $endDate = new \DateTime($this->creditTransaction2->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        $this->creditTransaction2->createRecurringSeries(
            $endDate,
            'biweekly'
        );
        $this->assertEquals(27, $this->user->transactions()->count());

        $response = $this->delete(
            route(
                'transactions.destroy',
                [
                    'id' => $this->creditTransaction2->id,
                    'delete_child_transactions' => true
                ]
            )
        );
        $response->assertStatus(302);
        $this->assertEquals(0, $this->user->transactions()->count());
    }

    #[Group('transactions')]
    public function test_destroy_parent_and_buddy()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $this->assertEquals(1, $this->user->transactions()->count());
        $endDate = new \DateTime($this->creditTransaction2->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P2Y'))->format('Y-m-d');
        $this->creditTransaction2->createBuddyTransaction(
            $this->savingsAccount,
            'buddy note'
        );
        $this->creditTransaction2->createRecurringSeries($endDate);
        $this->assertEquals(50, $this->user->transactions()->count());
        $response = $this->delete(
            route(
                'transactions.destroy',
                [
                    'id' => $this->creditTransaction2->id,
                ]
            )
        );
        $response->assertStatus(302);
        $this->assertEquals(48, $this->user->transactions()->count());
        $newParent = Transaction::whereColumn('parent_id', 'id')->first();
        $this->assertEquals($newParent->parent_id, $newParent->id);
        $series = Transaction::where('parent_id', $newParent->id)->get();
        foreach ($series as $trans) {
            $this->assertEquals($newParent->id, $trans->parent_id);
        }
        $response->assertStatus(302);
    }

    #[Group('transactions')]
    public function test_destroy_buddy_of_parent()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $this->assertEquals(1, $this->user->transactions()->count());
        $endDate = new \DateTime($this->creditTransaction2->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P2Y'))->format('Y-m-d');
        $buddyTrans = $this->creditTransaction2->createBuddyTransaction(
            $this->savingsAccount,
            'buddy note'
        );
        $buddyTrans->createRecurringSeries($endDate);
        $this->assertEquals(50, $this->user->transactions()->count());
        $response = $this->delete(
            route(
                'transactions.destroy',
                [
                    'id' => $this->creditTransaction2->id,
                ]
            )
        );
        $response->assertStatus(302);
        $this->assertEquals(48, $this->user->transactions()->count());
        $newParent = Transaction::whereColumn('parent_id', 'id')->first();
        $this->assertEquals($newParent->parent_id, $newParent->id);
        $series = Transaction::where('parent_id', $newParent->id)->get();
        foreach ($series as $trans) {
            $this->assertEquals($newParent->id, $trans->parent_id);
        }
        $response->assertStatus(302);
    }

    #[Group('transactions')]
    public function test_destroy_parent_and_recurring_buddies()
    {
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $this->assertEquals(1, $this->user->transactions()->count());
        $endDate = new \DateTime($this->creditTransaction2->transaction_date);
        $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        $this->creditTransaction2->createBuddyTransaction(
            $this->savingsAccount,
            'buddy note'
        );
        $this->creditTransaction2->createRecurringSeries(
            $endDate,
            'quarterly'
        );
        $this->assertEquals(10, $this->user->transactions()->count());
        $response = $this->delete(
            route(
                'transactions.destroy',
                [
                    'id' => $this->creditTransaction2->id,
                    'delete_child_transactions' => true
                ]
            )
        );
        $response->assertStatus(302);
        $this->assertEquals(0, $this->user->transactions()->count());
    }

    /*
     * Private helper functions
     */

    private function _checkTransaction(
        array $trans_to_check,
        array $expected_transactions,
        Account $account
    ) {
        $current_trans = array_filter(
            $expected_transactions,
            function ($trans) use ($trans_to_check) {
                return $trans->id === $trans_to_check['id'];
            }
        );
        $this->_checkValues($trans_to_check, array_pop($current_trans), $account);
    }
    private function _checkValues(array $trans_array, Transaction $transaction, Account $account)
    {
        $this->assertEquals($transaction->transaction_date, $trans_array['transaction_date']);
        $this->assertEquals($transaction->amount, $trans_array['amount_raw']);
        $this->assertEquals($transaction->amount / 100, $trans_array['amount']);
        $this->assertEquals($account->name, $trans_array['account']);
        $this->assertEquals($transaction->note, $trans_array['note']);
        $this->_checkCatVal($trans_array['categories'], $transaction);
    }
    private function _checkCatVal(array $categories, Transaction $transaction)
    {
        foreach ($categories as $cat) {
            $cat_data = $cat['cat_data'];
            $cat_id = $cat_data['cat_id'];
            $cat_obj = Category::find($cat_id);
            $this->assertEquals($cat_obj->name, $cat_data['name']);
            $percentage = null;
            foreach ($transaction->categories as $trans_cat) {
                if ($trans_cat->id === $cat_id) {
                    $percentage = $trans_cat->pivot->percentage;
                    break;
                }
            }
            $this->assertEquals($percentage / 100, $cat['percent']);
        }
    }
    private function _checkChildrenAfterPost(
        Transaction $parent,
        array $expectedCatPercents,
        int $expectedChildCount,
        int $expectedChildCatCount,
        Account $expectedChildAccount,
        bool $checkChildBuddy = false,
        Account $expectedChildBuddyAccount = null,
        int $expectedChildBuddyCatCount = null
    ) {
        $children = $parent->children();
        $this->assertEquals($expectedChildCount, $children->count());
        foreach ($children as $child) {
            $this->assertEquals($parent->amount, $child->amount);
            $this->assertEquals($expectedChildAccount->id, $child->account_id);
            $this->assertEquals($expectedChildCatCount, count($child->categories));
            foreach ($child->categories as $cat) {
                $this->assertEquals($expectedCatPercents[$cat->id], $cat->pivot->percentage / 100);
            }

            if ($checkChildBuddy) {
                $buddy = $child->buddyTransaction();
                $this->assertEquals($parent->amount, $buddy->amount);
                $this->assertEquals($expectedChildBuddyAccount->id, $buddy->account_id);
                $this->assertEquals($expectedChildBuddyCatCount, count($buddy->categories));
                foreach ($buddy->categories as $cat) {
                    $this->assertEquals($expectedCatPercents[$cat->id], $cat->pivot->percentage / 100);
                }
            }
            /* $buddy_trans = Transaction::where('buddy_id', $trans->id)->first(); */
            /* $this->assertEquals($parent_trans->amount, $buddy_trans->amount); */
            /* $this->isTrue($buddy_trans->credit); */
            /* $this->assertEquals($this->creditCardAccount->id, $buddy_trans->account_id); */
            /* $this->assertCount(2, $buddy_trans->categories); */
            /* foreach ($buddy_trans->categories as $cat) { */
            /*     $this->assertEquals($expected_cats[$cat->id], $cat->pivot->percentage / 100); */
            /* } */
        }
    }
}
