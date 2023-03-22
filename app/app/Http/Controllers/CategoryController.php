<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
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
    public function store(Request $request) : \Illuminate\Http\RedirectResponse
    {
        $current_user = Auth::user();
        $request->validate([
            'name' => [ 'required', 'max:50' ],
        ]);
        $cat = new Category();
        $cat->name = $request->name;
        $cat->hex_color = $request->color;
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
            'color' => [ 'required' ],
        ]);


        $category->name = $request->name;
        $category->hex_color = $request->color;
        $category->category_type_id = $request->category_type;
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
