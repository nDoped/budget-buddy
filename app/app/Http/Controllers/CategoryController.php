<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function store(Request $request) : \Illuminate\Http\RedirectResponse
    {
        $current_user = Auth::user();
        $request->validate([
            'name' => [ 'required', 'max:50' ],
        ]);
        $cat = new Category();
        $cat->name = $request->name;
        $cat->hex_color = $request->hex_color;
        $cat->user_id = $current_user->id;
        $cat->category_type_id = $request->category_type;
        $cat->save();
        return redirect()->route('settings.categories')->with('message', 'Successfully Created Category');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Category $category) : \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => [ 'required', 'max:50' ],
            'hex_color' => [ 'required' ],
            'active' => [ 'required' ],
        ]);

        $category->name = $request->name;
        $category->hex_color = $request->hex_color;
        $category->category_type_id = $request->category_type;
        $category->active = $request->active;
        $category->save();
        return redirect()->route('settings.categories');
    }

    /**
     * Merge a category into another category.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function merge(Request $request, Category $category) : \Illuminate\Http\RedirectResponse
    {
        $current_user = Auth::user();
        if ($category->user_id !== $current_user->id) {
            abort(403);
        }

        $request->validate([
            'target_category_id' => [ 'required', 'exists:categories,id' ],
        ]);

        $targetCategory = Category::findOrFail($request->target_category_id);

        if ($targetCategory->user_id !== $current_user->id) {
            abort(403);
        }

        if ($category->id === $targetCategory->id) {
            return redirect()->back()->withErrors([
                'message' => 'Cannot merge a category into itself'
            ]);
        }

        DB::transaction(function () use ($category, $targetCategory) {
            $sourcePivots = DB::table('category_transaction')
                ->where('category_id', $category->id)
                ->get();

            foreach ($sourcePivots as $pivot) {
                $targetPivot = DB::table('category_transaction')
                    ->where('transaction_id', $pivot->transaction_id)
                    ->where('category_id', $targetCategory->id)
                    ->first();

                if ($targetPivot) {
                    $newPercentage = min($pivot->percentage + $targetPivot->percentage, 10000);
                    DB::table('category_transaction')
                        ->where('id', $targetPivot->id)
                        ->update(['percentage' => $newPercentage]);
                    DB::table('category_transaction')
                        ->where('id', $pivot->id)
                        ->delete();
                } else {
                    DB::table('category_transaction')
                        ->where('id', $pivot->id)
                        ->update(['category_id' => $targetCategory->id]);
                }
            }

            $category->delete();
        });

        return redirect()->back()->with('message', "{$category->name} merged into {$targetCategory->name}");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) : \Illuminate\Http\RedirectResponse
    {
        $category = Category::where('id', '=', $request->id)->first();
        if ($category) {
            $linked_transactions = $category->transactions();
            if ($linked_transactions->count() > 0) {
                return redirect()->back()->withErrors([
                    'message' => 'This category appears on at least 1 transaction and cannot be deleted. It must be merged with another category or removed from all transactions before it can be deleted.'
                ]);
            }
            Category::destroy($request->id);
        } else {
            return redirect()->back()->withErrors('Invalid category id');
        }
        return redirect()->route('settings.categories');
    }
}
