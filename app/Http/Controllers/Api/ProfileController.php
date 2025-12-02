<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Complaint;

class ProfileController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        
        // Get complaint statistics
        $totalComplaints = Complaint::where('user_id', $user->id)->count();
        $resolvedComplaints = Complaint::where('user_id', $user->id)->where('status', 'resolved')->count();
        $pendingComplaints = Complaint::where('user_id', $user->id)->where('status', 'pending')->count();
        $inProgressComplaints = Complaint::where('user_id', $user->id)->whereIn('status', ['in_review', 'in_progress'])->count();
        
        // Calculate Level based on points
        // 10 points per complaint, 50 points per resolved complaint
        $points = ($totalComplaints * 10) + ($resolvedComplaints * 50);
        
        $level = $this->calculateLevel($points);
        $nextLevel = $this->getNextLevel($level['current_level']);
        $progress = $this->calculateProgress($points, $level['min_points'], $nextLevel['min_points']);

        // Get recent activity
        $recentActivity = Complaint::where('user_id', $user->id)
            ->with('category')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => [
                    'total' => $totalComplaints,
                    'resolved' => $resolvedComplaints,
                    'pending' => $pendingComplaints,
                    'in_progress' => $inProgressComplaints,
                    'points' => $points,
                ],
                'gamification' => [
                    'current_level' => $level['title'],
                    'current_badge' => $level['badge'],
                    'next_level' => $nextLevel['title'],
                    'progress_percentage' => $progress,
                    'points_needed' => $nextLevel['min_points'] - $points,
                ],
                'recent_activity' => $recentActivity
            ]
        ]);
    }

    private function calculateLevel($points)
    {
        $levels = [
            ['min_points' => 0, 'title' => 'مواطن جديد', 'badge' => '🌱'],
            ['min_points' => 100, 'title' => 'مواطن فعال', 'badge' => '⭐'],
            ['min_points' => 500, 'title' => 'مواطن مميز', 'badge' => '🌟'],
            ['min_points' => 1000, 'title' => 'بطل الحي', 'badge' => '🏆'],
            ['min_points' => 2500, 'title' => 'سفير المجتمع', 'badge' => '👑'],
        ];

        $currentLevel = $levels[0];

        foreach ($levels as $level) {
            if ($points >= $level['min_points']) {
                $currentLevel = $level;
            } else {
                break;
            }
        }

        return [
            'current_level' => $currentLevel['title'], // Return just the title for simplicity in logic above, but array structure here
            'title' => $currentLevel['title'],
            'badge' => $currentLevel['badge'],
            'min_points' => $currentLevel['min_points']
        ];
    }

    private function getNextLevel($currentTitle)
    {
        $levels = [
            ['min_points' => 0, 'title' => 'مواطن جديد'],
            ['min_points' => 100, 'title' => 'مواطن فعال'],
            ['min_points' => 500, 'title' => 'مواطن مميز'],
            ['min_points' => 1000, 'title' => 'بطل الحي'],
            ['min_points' => 2500, 'title' => 'سفير المجتمع'],
            ['min_points' => 10000, 'title' => 'القمة'], // Cap
        ];

        $found = false;
        foreach ($levels as $level) {
            if ($found) return $level;
            if ($level['title'] === $currentTitle) $found = true;
        }

        return end($levels);
    }

    private function calculateProgress($points, $currentLevelMin, $nextLevelMin)
    {
        if ($nextLevelMin <= $currentLevelMin) return 100;
        
        $totalRange = $nextLevelMin - $currentLevelMin;
        $progress = $points - $currentLevelMin;
        
        return min(100, max(0, round(($progress / $totalRange) * 100)));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', new \App\Rules\NoProfanity],
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(09\d{8}|(\+963|00963)9\d{8})$/'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data' => $user
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!password_verify($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ], 400);
        }

        $user->update([
            'password' => bcrypt($validated['new_password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            
            $user->update(['avatar' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الصورة الشخصية بنجاح',
                'data' => [
                    'avatar' => $path,
                    'avatar_url' => asset('storage/' . $path)
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'لم يتم رفع أي صورة'
        ], 400);
    }
    public function notifications(Request $request)
    {
        $user = $request->user();
        
        // Get updates for user's complaints
        $notifications = \App\Models\ComplaintUpdate::whereHas('complaint', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('is_public', true)
            ->with('complaint:id,title,tracking_number')
            ->select('id', 'complaint_id', 'user_id', 'comment as message', 'created_at', 'read_at')
            ->latest()
            ->take(20)
            ->get();
            
        $unreadCount = \App\Models\ComplaintUpdate::whereHas('complaint', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('is_public', true)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    public function markNotificationsAsRead(Request $request)
    {
        $user = $request->user();

        \App\Models\ComplaintUpdate::whereHas('complaint', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('is_public', true)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الإشعارات'
        ]);
    }
}
