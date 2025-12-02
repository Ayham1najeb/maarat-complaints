<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;

class AreaController extends Controller
{
    /**
     * قائمة المناطق
     */
    public function index()
    {
        $areas = \Illuminate\Support\Facades\Cache::remember('areas_list', 60 * 60 * 24, function () {
            return Area::all();
        });

        return response()->json([
            'success' => true,
            'data' => $areas
        ]);
    }
}
