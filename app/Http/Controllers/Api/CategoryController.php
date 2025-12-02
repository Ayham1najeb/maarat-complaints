<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * قائمة التصنيفات
     */
    public function index()
    {
        $categories = \Illuminate\Support\Facades\Cache::remember('categories_list', 60 * 60 * 24, function () {
            return Category::active()
                ->ordered()
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}
