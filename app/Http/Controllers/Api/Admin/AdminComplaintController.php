<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Complaint;
use App\Models\ComplaintUpdate;
use Illuminate\Http\Request;

class AdminComplaintController extends Controller
{
    /**
     * قائمة الشكاوي (مع فلترة)
     */
    public function index(Request $request)
    {
        $query = Complaint::with(['category', 'area', 'user', 'assignment.assignedTo'])
            ->latest();

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب الأولوية
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // فلترة حسب التصنيف
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // فلترة حسب المنطقة
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        // بحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhere('citizen_name', 'like', "%{$search}%");
            });
        }

        $complaints = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $complaints
        ]);
    }

    /**
     * تفاصيل شكوى
     */
    public function show($id)
    {
        $complaint = Complaint::with([
            'category',
            'area',
            'user',
            'images',
            'updates.user',
            'assignments.assignedTo',
            'assignments.assignedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $complaint
        ]);
    }

    /**
     * تحديث حالة الشكوى
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_review,in_progress,resolved,rejected',
            'comment' => 'nullable|string',
            'is_public' => 'nullable|boolean',
        ]);

        $complaint = Complaint::findOrFail($id);
        $oldStatus = $complaint->status;

        // تحديث الحالة
        $complaint->update([
            'status' => $request->status,
        ]);

        // تحديث تواريخ
        if ($request->status === 'in_review' && !$complaint->reviewed_at) {
            $complaint->update(['reviewed_at' => now()]);
        }

        if ($request->status === 'resolved' && !$complaint->resolved_at) {
            $complaint->update(['resolved_at' => now()]);
        }

        // إضافة تحديث
        ComplaintUpdate::create([
            'complaint_id' => $complaint->id,
            'user_id' => auth()->id(),
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'comment' => $request->comment ?? 'تم تغيير الحالة',
            'is_public' => $request->is_public ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الحالة بنجاح',
            'data' => $complaint->fresh()
        ]);
    }

    /**
     * تحديث الأولوية
     */
    public function updatePriority(Request $request, $id)
    {
        $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $complaint = Complaint::findOrFail($id);
        $complaint->update(['priority' => $request->priority]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الأولوية بنجاح',
            'data' => $complaint
        ]);
    }

    /**
     * تعيين شكوى لموظف
     */
    public function assign(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $complaint = Complaint::findOrFail($id);

        // إنشاء تعيين جديد
        $assignment = Assignment::create([
            'complaint_id' => $complaint->id,
            'assigned_to' => $request->assigned_to,
            'assigned_by' => auth()->id(),
            'notes' => $request->notes,
            'assigned_at' => now(),
        ]);

        // تحديث حالة الشكوى
        if ($complaint->status === 'pending') {
            $complaint->update(['status' => 'in_review']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الشكوى بنجاح',
            'data' => $assignment->load(['assignedTo', 'assignedBy'])
        ]);
    }

    /**
     * إضافة تحديث على الشكوى
     */
    public function addUpdate(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string',
            'is_public' => 'nullable|boolean',
        ]);

        $complaint = Complaint::findOrFail($id);

        $update = ComplaintUpdate::create([
            'complaint_id' => $complaint->id,
            'user_id' => auth()->id(),
            'old_status' => $complaint->status,
            'new_status' => $complaint->status,
            'comment' => $request->comment,
            'is_public' => $request->is_public ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التحديث بنجاح',
            'data' => $update->load('user')
        ]);
    }

    /**
     * حذف شكوى
     */
    public function destroy($id)
    {
        $complaint = Complaint::findOrFail($id);
        $complaint->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الشكوى بنجاح'
        ]);
    }
}
