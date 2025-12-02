<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    /**
     * إنشاء تصنيف جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التصنيف بنجاح',
            'data' => $category
        ], 201);
    }

    /**
     * تحديث تصنيف
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التصنيف بنجاح',
            'data' => $category
        ]);
    }

    /**
     * حذف تصنيف
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // التحقق من عدم وجود شكاوي مرتبطة
        if ($category->complaints()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف التصنيف لأنه يحتوي على شكاوي'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التصنيف بنجاح'
        ]);
    }
}
