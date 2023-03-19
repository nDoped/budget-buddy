<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Category  $cat
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category) : \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => [ 'required', 'max:50' ],
            'color' => [ 'required' ],
        ]);


        $category->name = $request->name;
        $category->hex_color = $request->color;
        switch ($request->category_type) {
        case 'extra_expense':
            $category->extra_expense = true;
            $category->primary_income = false;
            $category->secondary_income = false;
            $category->regular_expense = false;
            $category->recurring_expense = false;
            $category->housing_expense = false;
            $category->utility_expense = false;
            break;

        case 'regular_expense':
            $category->regular_expense = true;
            $category->primary_income = false;
            $category->secondary_income = false;
            $category->extra_expense = false;
            $category->recurring_expense = false;
            $category->housing_expense = false;
            $category->utility_expense = false;
            break;

        case 'recurring_expense':
            $category->recurring_expense = true;
            $category->primary_income = false;
            $category->secondary_income = false;
            $category->regular_expense = false;
            $category->extra_expense = false;
            $category->housing_expense = false;
            $category->utility_expense = false;
            break;

        case 'housing_expense':
            $category->housing_expense = true;
            $category->primary_income = false;
            $category->secondary_income = false;
            $category->regular_expense = false;
            $category->recurring_expense = false;
            $category->extra_expense = false;
            $category->utility_expense = false;
            break;

        case 'utility_expense':
            $category->utility_expense = true;
            $category->primary_income = false;
            $category->secondary_income = false;
            $category->regular_expense = false;
            $category->recurring_expense = false;
            $category->extra_expense = false;
            $category->housing_expense = false;
            break;

        case 'primary_income':
            $category->primary_income = true;
            $category->secondary_income = false;
            $category->regular_expense = false;
            $category->recurring_expense = false;
            $category->extra_expense = false;
            $category->housing_expense = false;
            $category->utility_expense = false;
            break;

        case 'secondary_income':
            $category->secondary_income = true;
            $category->primary_income = false;
            $category->regular_expense = false;
            $category->recurring_expense = false;
            $category->extra_expense = false;
            $category->housing_expense = false;
            $category->utility_expense = false;
            break;
        }
        $category->save();
        return redirect()->route('settings.categories');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) : \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'id' => [ 'required' ],
        ]);
        $category = Category::find($request->id);
        if ($category) {
            $linked_transactions = $category->transactions();
            if ($linked_transactions->count() > 0) {
                return redirect()->back()->withErrors([
                    'message' => 'This category appears on at least 1 transaction and cannot be deleted'
                ]);
            }
            Category::destroy($request->id);
        } else {
            return redirect()->back()->withErrors('shit went down');
        }
        return redirect()->route('settings.categories');
    }
}
