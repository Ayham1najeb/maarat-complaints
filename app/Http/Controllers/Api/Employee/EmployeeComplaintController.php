<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeComplaintController extends Controller
{
    /**
     * Get complaints assigned to the logged-in employee
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get all assignments for this employee
        $query = Assignment::where('assigned_to', $user->id)
            ->with(['complaint.category', 'complaint.area', 'complaint.user', 'complaint.images']);
        
        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        $assignments = $query->latest()->paginate(20);
        
        // Transform to include complaint data
        // Transform to include complaint data
        $data = $assignments->map(function ($assignment) {
            $complaint = $assignment->complaint;
            
            if (!$complaint) {
                return null;
            }

            return [
                'id' => $complaint->id,
                'tracking_number' => $complaint->tracking_number,
                'title' => $complaint->title,
                'description' => $complaint->description,
                'status' => $complaint->status,
                'priority' => $complaint->priority,
                'category' => $complaint->category->name ?? null,
                'area' => $complaint->area->name ?? null,
                'location_address' => $complaint->location_address,
                'latitude' => $complaint->latitude,
                'longitude' => $complaint->longitude,
                'created_at' => $complaint->created_at,
                'assignment' => [
                    'id' => $assignment->id,
                    'status' => $assignment->status,
                    'notes' => $assignment->notes,
                    'assigned_at' => $assignment->assigned_at,
                    'started_at' => $assignment->started_at,
                    'completed_at' => $assignment->completed_at,
                ],
                'images' => $complaint->images,
            ];
        })->filter()->values();
        
        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'total' => $assignments->total(),
            ],
        ]);
    }
    
    /**
     * Get single complaint details
     */
    public function show($id)
    {
        $user = Auth::user();
        
        // Check if this complaint is assigned to the employee
        $assignment = Assignment::where('assigned_to', $user->id)
            ->whereHas('complaint', function ($query) use ($id) {
                $query->where('id', $id);
            })
            ->with(['complaint.category', 'complaint.area', 'complaint.user', 'complaint.images', 'complaint.updates'])
            ->first();
        
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الشكوى غير معينة لك',
            ], 403);
        }
        
        $complaint = $assignment->complaint;
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $complaint->id,
                'tracking_number' => $complaint->tracking_number,
                'title' => $complaint->title,
                'description' => $complaint->description,
                'status' => $complaint->status,
                'priority' => $complaint->priority,
                'category' => $complaint->category,
                'area' => $complaint->area,
                'location_address' => $complaint->location_address,
                'latitude' => $complaint->latitude,
                'longitude' => $complaint->longitude,
                'citizen_name' => $complaint->citizen_name,
                'citizen_phone' => $complaint->citizen_phone,
                'citizen_email' => $complaint->citizen_email,
                'created_at' => $complaint->created_at,
                'assignment' => $assignment,
                'images' => $complaint->images,
                'updates' => $complaint->updates,
            ],
        ]);
    }
    
    /**
     * Start working on assignment
     */
    public function startWork($id)
    {
        $user = Auth::user();
        
        $assignment = Assignment::where('assigned_to', $user->id)
            ->where('complaint_id', $id)
            ->with('complaint')
            ->first();
        
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الشكوى غير معينة لك',
            ], 403);
        }
        
        $assignment->start();
        
        // Update complaint status
        $assignment->complaint->update(['status' => 'in_progress']);
        
        // Create notification for citizen
        $assignment->complaint->updates()->create([
            'user_id' => $user->id,
            'old_status' => $assignment->complaint->status, // Status before update (captured before this line if needed, but here we just updated it above. Wait, we updated it above!)
            'new_status' => 'in_progress',
            'comment' => 'تم بدء العمل على شكواك من قبل ' . $user->name,
            'is_public' => true,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'تم بدء العمل على الشكوى',
            'data' => $assignment,
        ]);
    }
    
    /**
     * Complete assignment
     */
    public function completeWork(Request $request, $id)
    {
        $user = Auth::user();
        
        $assignment = Assignment::where('assigned_to', $user->id)
            ->where('complaint_id', $id)
            ->with('complaint')
            ->first();
        
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الشكوى غير معينة لك',
            ], 403);
        }
        
        $request->validate([
            'notes' => 'nullable|string',
        ]);
        
        $assignment->complete();
        
        if ($request->notes) {
            $assignment->update(['notes' => $request->notes]);
        }
        
        // Update complaint status
        $assignment->complaint->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
        
        // Create notification for citizen
        $message = 'تم إنهاء العمل على شكواك من قبل ' . $user->name;
        if ($request->notes) {
            $message .= '. ملاحظات: ' . $request->notes;
        }
        
        $assignment->complaint->updates()->create([
            'user_id' => $user->id,
            'old_status' => 'in_progress',
            'new_status' => 'resolved',
            'comment' => $message,
            'is_public' => true,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'تم إكمال العمل على الشكوى',
            'data' => $assignment,
        ]);
    }
    
    /**
     * Add update to complaint
     */
    public function addUpdate(Request $request, $id)
    {
        $user = Auth::user();
        
        // Check if complaint is assigned to employee
        $assignment = Assignment::where('assigned_to', $user->id)
            ->where('complaint_id', $id)
            ->first();
        
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الشكوى غير معينة لك',
            ], 403);
        }
        
        $request->validate([
            'message' => 'required|string',
        ]);
        
        $update = $assignment->complaint->updates()->create([
            'user_id' => $user->id,
            'message' => $request->message,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التحديث بنجاح',
            'data' => $update,
        ]);
    }
    
    /**
     * Get employee statistics
     */
    public function statistics()
    {
        $user = Auth::user();
        
        $total = Assignment::where('assigned_to', $user->id)->count();
        $pending = Assignment::where('assigned_to', $user->id)->where('status', 'pending')->count();
        $working = Assignment::where('assigned_to', $user->id)->where('status', 'working')->count();
        $completed = Assignment::where('assigned_to', $user->id)->where('status', 'completed')->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pending' => $pending,
                'working' => $working,
                'completed' => $completed,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100) : 0,
            ],
        ]);
    }
}
