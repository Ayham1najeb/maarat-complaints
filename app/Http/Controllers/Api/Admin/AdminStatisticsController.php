<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStatisticsController extends Controller
{
    /**
     * نظرة عامة على الإحصائيات
     */
    public function overview()
    {
        $total = Complaint::count();
        $pending = Complaint::where('status', 'pending')->count();
        $inReview = Complaint::where('status', 'in_review')->count();
        $inProgress = Complaint::where('status', 'in_progress')->count();
        $resolved = Complaint::where('status', 'resolved')->count();
        $rejected = Complaint::where('status', 'rejected')->count();

        // معدل الحل
        $resolutionRate = $total > 0 ? round(($resolved / $total) * 100, 2) : 0;

        // متوسط وقت الحل
        $avgResolutionTime = Complaint::whereNotNull('resolved_at')
            ->get()
            ->avg(function ($complaint) {
                return $complaint->created_at->diffInHours($complaint->resolved_at);
            });

        // الشكاوي الجديدة اليوم
        $todayNew = Complaint::whereDate('created_at', today())->count();

        // الشكاوي المحلولة اليوم
        $todayResolved = Complaint::whereDate('resolved_at', today())->count();

        // إحصائيات المستخدمين
        $totalUsers = \App\Models\User::count();
        $totalCitizens = \App\Models\User::where('role', 'citizen')->count();
        $totalEmployees = \App\Models\User::where('role', 'employee')->count();

        // إحصائيات الزوار
        $totalVisits = \App\Models\Visit::count();
        $todayVisits = \App\Models\Visit::whereDate('visited_at', today())->count();
        $monthlyVisits = \App\Models\Visit::where('visited_at', '>=', now()->startOfMonth())
            ->distinct('ip_address')
            ->count('ip_address');

        return response()->json([
            'success' => true,
            'data' => [
                'visits' => [
                    'total' => $totalVisits,
                    'today' => $todayVisits,
                    'this_month' => $monthlyVisits,
                ],
                'users' => [
                    'total' => $totalUsers,
                    'citizens' => $totalCitizens,
                    'employees' => $totalEmployees,
                ],
                'total' => $total,
                'pending' => $pending,
                'in_review' => $inReview,
                'in_progress' => $inProgress,
                'resolved' => $resolved,
                'rejected' => $rejected,
                'resolution_rate' => $resolutionRate,
                'avg_resolution_time_hours' => round($avgResolutionTime ?? 0, 1),
                'today_new' => $todayNew,
                'today_resolved' => $todayResolved,
            ]
        ]);
    }

    /**
     * إحصائيات حسب التصنيف
     */
    public function byCategory()
    {
        $stats = Complaint::select('category_id', DB::raw('count(*) as total'))
            ->with('category:id,name,name_ar,icon,color')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'total' => $item->total,
                    'pending' => Complaint::where('category_id', $item->category_id)
                        ->where('status', 'pending')->count(),
                    'resolved' => Complaint::where('category_id', $item->category_id)
                        ->where('status', 'resolved')->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * إحصائيات حسب المنطقة
     */
    public function byArea()
    {
        $stats = Complaint::select('area_id', DB::raw('count(*) as total'))
            ->with('area:id,name')
            ->whereNotNull('area_id')
            ->groupBy('area_id')
            ->get()
            ->map(function ($item) {
                return [
                    'area' => $item->area,
                    'total' => $item->total,
                    'pending' => Complaint::where('area_id', $item->area_id)
                        ->where('status', 'pending')->count(),
                    'resolved' => Complaint::where('area_id', $item->area_id)
                        ->where('status', 'resolved')->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * إحصائيات حسب الحالة
     */
    public function byStatus()
    {
        $statuses = ['pending', 'in_review', 'in_progress', 'resolved', 'rejected'];

        $stats = collect($statuses)->map(function ($status) {
            return [
                'status' => $status,
                'count' => Complaint::where('status', $status)->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * اتجاهات الشكاوي (آخر 30 يوم)
     */
    public function trends(Request $request)
    {
        $days = $request->days ?? 30;

        $trends = Complaint::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "resolved" then 1 else 0 end) as resolved'),
                DB::raw('sum(case when status = "pending" then 1 else 0 end) as pending')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }
}
