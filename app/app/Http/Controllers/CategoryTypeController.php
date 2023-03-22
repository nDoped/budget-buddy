<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CategoryType;

class CategoryTypeController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $current_user = Auth::user();
        $request->validate([
            'name' => ['required', 'max:50'],
        ]);
        $cat_type = new CategoryType();
        $cat_type->name = $request->name;
        $cat_type->note = $request->note;
        $cat_type->hex_color = $request->color;
        $cat_type->user_id = $current_user->id;
        $cat_type->save();
        return redirect()->route('settings.category_types');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CategoryType $categoryType)
    {
        $categoryType->name = $request->name;
        $categoryType->note = $request->note;
        $categoryType->hex_color = $request->color;
        $categoryType->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id) : \Illuminate\Http\RedirectResponse
    {
        $cat_type = CategoryType::find($id);
        if ($cat_type) {
            $linked_categories = $cat_type->categories();
            if ($linked_categories->count() > 0) {
                return redirect()->back()->withErrors([
                    'message' => 'This category type is used by at least 1 category and cannot be deleted'
                ]);
            }
            CategoryType::destroy($id);
        } else {
            return redirect()->back()->withErrors('shit went down');
        }
        return redirect()->route('settings.category_types');
    }
}
