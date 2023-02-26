<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Requests\TransactionPostRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\AccountType;
use App\Models\User;

class CategoryController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Request $request
     * @param  \App\Models\Category  $cat
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $cat)
    {
        $request->validate([
            'name' => [ 'required', 'max:50' ],
            'color' => [ 'required' ],
        ]);

        /*
        Log::info([
          'app/Http/Controllers/CategoryController.php:82 all' => $request->all(),
        ]);
         */
        $cat = Category::find($request->id);
        $cat->name = $request->name;
        $cat->hex_color = $request->color;
        $cat->include_in_expense_breakdown = $request->include_in_expense_breakdown;
        $cat->save();
        return redirect()->route('settings.categories');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Requests\Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => [ 'required' ],
        ]);
        $linked_transactions = Category::find($request->id)->transactions();
        if ($linked_transactions->count() > 0) {
            return redirect()->back()->withErrors([
                'message' => 'This category appears on at least 1 transaction and cannot be deleted'
            ]);
        }
        Category::destroy($request->id);
        return redirect()->route('settings.categories');
    }
}
