<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CategorySubtype;
use App\Services\ActivityLogService;

class CategorySubtypeController extends Controller
{
    public function store(Request $request)
    {
        $current_user = Auth::user();
        $request->validate([
            'name' => ['required', 'max:50'],
            'category_type_id' => ['required', 'exists:category_types,id'],
        ]);
        $subtype = new CategorySubtype();
        $subtype->name = $request->name;
        $subtype->category_type_id = $request->category_type_id;
        $subtype->user_id = $current_user->id;
        $subtype->save();
        ActivityLogService::log(
            'category_subtype_created',
            "Created category subtype #{$subtype->id}: {$subtype->name}"
        );
        return redirect()->route('settings.category_subtypes')->with('message', 'Successfully Created Category Subtype');
    }

    public function update(Request $request, CategorySubtype $categorySubtype)
    {
        $request->validate([
            'name' => ['required', 'max:50'],
        ]);
        $old = [
            'name' => $categorySubtype->name,
        ];
        $categorySubtype->name = $request->name;
        $categorySubtype->save();
        $changes = array_filter([
            'name' => ['old' => $old['name'], 'new' => $categorySubtype->name],
        ], fn($v) => $v['old'] != $v['new']);
        ActivityLogService::log(
            'category_subtype_updated',
            "Updated category subtype #{$categorySubtype->id}: {$categorySubtype->name}",
            $changes
        );
        return redirect()->route('settings.category_subtypes');
    }

    public function destroy(Request $request) : \Illuminate\Http\RedirectResponse
    {
        $subtype = CategorySubtype::where('id', '=', $request->id)->first();
        if ($subtype) {
            $linkedCategories = $subtype->categories();
            if ($linkedCategories->count() > 0) {
                return redirect()->back()->withErrors([
                    'message' => 'This subtype is used by at least 1 category and cannot be deleted'
                ]);
            }
            ActivityLogService::log(
                'category_subtype_deleted',
                "Deleted category subtype #{$subtype->id}: {$subtype->name}"
            );
            CategorySubtype::destroy($subtype->id);
        } else {
            return redirect()->back()->withErrors('Invalid category subtype id');
        }
        return redirect()->route('settings.category_subtypes');
    }
}
