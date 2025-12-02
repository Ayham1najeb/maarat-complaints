<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class ComplaintController extends Controller
{
    /**
     * التحقق من حد الشكاوى اليومي والشهري
     */
    private function checkRateLimit($citizenPhone, $citizenEmail)
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        // عدد الشكاوى اليومية
        $dailyCount = Complaint::where(function($query) use ($citizenPhone, $citizenEmail) {
                $query->where('citizen_phone', $citizenPhone);
                if ($citizenEmail) {
                    $query->orWhere('citizen_email', $citizenEmail);
                }
            })
            ->where('created_at', '>=', $today)
            ->count();

        // عدد الشكاوى الشهرية
        $monthlyCount = Complaint::where(function($query) use ($citizenPhone, $citizenEmail) {
                $query->where('citizen_phone', $citizenPhone);
                if ($citizenEmail) {
                    $query->orWhere('citizen_email', $citizenEmail);
                }
            })
            ->where('created_at', '>=', $monthStart)
            ->count();

        // التحقق من الحد اليومي (3 شكاوى)
        if ($dailyCount >= 3) {
            return [
                'exceeded' => true,
                'type' => 'daily',
                'message' => 'تم تقديم عدد كبير من الشكاوى اليوم، يرجى الانتظار 24 ساعة.',
                'current_count' => $dailyCount,
                'limit' => 3,
                'reset_at' => now()->addDay()->startOfDay()->toIso8601String()
            ];
        }

        // التحقق من الحد الشهري (10 شكاوى)
        if ($monthlyCount >= 10) {
            return [
                'exceeded' => true,
                'type' => 'monthly',
                'message' => 'لقد وصلت إلى الحد الأقصى من الشكاوى لهذا الشهر (10 شكاوى). يرجى الانتظار حتى الشهر القادم.',
                'current_count' => $monthlyCount,
                'limit' => 10,
                'reset_at' => now()->addMonth()->startOfMonth()->toIso8601String()
            ];
        }

        return ['exceeded' => false];
    }

    /**
     * التحقق من الشكاوى المكررة
     * ملاحظة مهمة: يتم الفحص فقط ضمن شكاوى نفس المستخدم لتجنب الخلط بين المواطنين
     */
    private function checkDuplicateComplaint($userId, $citizenPhone, $citizenEmail, $title, $categoryId, $areaId)
    {
        // البحث عن شكاوى مشابهة في آخر 30 يوم
        $thirtyDaysAgo = now()->subDays(30);

        // إذا كان المستخدم مسجل دخول، نفحص فقط شكاواه (user_id)
        // إذا لم يكن مسجل، نفحص بناءً على رقم الهاتف والبريد
        $query = Complaint::query();
        
        if ($userId) {
            // مستخدم مسجل: نفحص فقط شكاواه الخاصة
            $query->where('user_id', $userId);
        } else {
            // مستخدم غير مسجل: نفحص بناءً على رقم الهاتف/البريد
            $query->where(function($q) use ($citizenPhone, $citizenEmail) {
                $q->where('citizen_phone', $citizenPhone);
                if ($citizenEmail) {
                    $q->orWhere('citizen_email', $citizenEmail);
                }
            });
        }

        $similarComplaints = $query
            ->where('category_id', $categoryId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->get();

        foreach ($similarComplaints as $complaint) {
            // حساب نسبة التشابه بين العناوين
            $similarity = $this->calculateSimilarity($title, $complaint->title);
            
            // إذا كان التشابه أكثر من 70% ونفس المنطقة
            if ($similarity > 70 && $complaint->area_id == $areaId) {
                return [
                    'is_duplicate' => true,
                    'message' => 'يبدو أنك قدمت شكوى مشابهة سابقًا — هل تريد متابعة الشكوى القديمة؟',
                    'existing_complaint' => [
                        'id' => $complaint->id,
                        'tracking_number' => $complaint->tracking_number,
                        'title' => $complaint->title,
                        'status' => $complaint->status,
                        'created_at' => $complaint->created_at->format('Y-m-d'),
                        'similarity_percentage' => round($similarity, 2)
                    ]
                ];
            }
        }

        return ['is_duplicate' => false];
    }

    /**
     * حساب نسبة التشابه بين نصين باستخدام خوارزمية Levenshtein
     */
    private function calculateSimilarity($str1, $str2)
    {
        $str1 = mb_strtolower(trim($str1));
        $str2 = mb_strtolower(trim($str2));

        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);

        if ($len1 == 0 || $len2 == 0) {
            return 0;
        }

        $levenshtein = levenshtein($str1, $str2);
        $maxLen = max($len1, $len2);
        
        return (1 - ($levenshtein / $maxLen)) * 100;
    }

    /**
     * تقديم شكوى جديدة
     */
    public function store(Request $request)
    {
        $request->validate([
            'citizen_name' => ['required', 'string', 'max:255', new \App\Rules\NoProfanity],
            'citizen_phone' => 'required|string|max:20',
            'citizen_email' => 'nullable|email',
            'category_id' => 'required|exists:categories,id',
            'area_id' => 'nullable|exists:areas,id',
            'title' => ['required', 'string', 'max:255', new \App\Rules\NoProfanity],
            'description' => ['required', 'string', new \App\Rules\NoProfanity],
            'location_address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'images' => 'nullable|array|max:5',
            'images.*' => ['image', 'mimes:jpeg,png,jpg', 'max:5120', new \App\Rules\SafeImageContent], // 5MB
            'force_submit' => 'nullable|boolean', // للسماح بتجاوز تحذير التكرار
        ]);

        // التحقق من حد الشكاوى (Rate Limiting)
        $rateLimitCheck = $this->checkRateLimit(
            $request->citizen_phone,
            $request->citizen_email
        );

        if ($rateLimitCheck['exceeded']) {
            return response()->json([
                'success' => false,
                'error_type' => 'rate_limit_exceeded',
                'message' => $rateLimitCheck['message'],
                'data' => [
                    'limit_type' => $rateLimitCheck['type'],
                    'current_count' => $rateLimitCheck['current_count'],
                    'limit' => $rateLimitCheck['limit'],
                    'reset_at' => $rateLimitCheck['reset_at']
                ]
            ], 429); // HTTP 429 Too Many Requests
        }

        // التحقق من الشكاوى المكررة (Duplicate Detection)
        // يتم تجاوز هذا الفحص إذا كان force_submit = true
        if (!$request->force_submit) {
            $duplicateCheck = $this->checkDuplicateComplaint(
                Auth::guard('sanctum')->id(), // user_id للمستخدم المسجل أو null
                $request->citizen_phone,
                $request->citizen_email,
                $request->title,
                $request->category_id,
                $request->area_id
            );

            if ($duplicateCheck['is_duplicate']) {
                return response()->json([
                    'success' => false,
                    'error_type' => 'duplicate_complaint',
                    'message' => $duplicateCheck['message'],
                    'data' => $duplicateCheck['existing_complaint']
                ], 409); // HTTP 409 Conflict
            }
        }

        // إنشاء الشكوى
        $complaint = Complaint::create([
            'user_id' => Auth::guard('sanctum')->id(),
            'citizen_name' => $request->citizen_name,
            'citizen_phone' => $request->citizen_phone,
            'citizen_email' => $request->citizen_email,
            'category_id' => $request->category_id,
            'area_id' => $request->area_id,
            'title' => $request->title,
            'description' => $request->description,
            'location_address' => $request->location_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // رفع الصور
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('complaints/' . $complaint->id, 'public');

                ComplaintImage::create([
                    'complaint_id' => $complaint->id,
                    'image_path' => $path,
                    'file_size' => $image->getSize(),
                    'mime_type' => $image->getMimeType(),
                    'order' => $index,
                ]);
            }
        }

        // تعيين تلقائي للموظف المختص بهذا التصنيف
        $this->autoAssignToEmployee($complaint);

        $complaint->load(['category', 'area', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم الشكوى بنجاح',
            'data' => [
                'complaint' => $complaint,
                'tracking_number' => $complaint->tracking_number,
            ]
        ], 201);
    }

    /**
     * تعيين تلقائي للشكوى لموظف مختص
     */
    private function autoAssignToEmployee($complaint)
    {
        // البحث عن موظف مختص بنفس التصنيف
        $employee = \App\Models\User::where('role', 'employee')
            ->where('category_id', $complaint->category_id)
            ->first();

        if ($employee) {
            // إنشاء تعيين للموظف
            \App\Models\Assignment::create([
                'complaint_id' => $complaint->id,
                'assigned_to' => $employee->id,
                'assigned_by' => null, // تعيين تلقائي
                'status' => 'pending',
                'assigned_at' => now(),
                'notes' => 'تعيين تلقائي حسب التصنيف',
            ]);

            // تحديث حالة الشكوى
            $complaint->update(['status' => 'in_review']);
        }
    }

    /**
     * متابعة شكوى برقم التتبع
     */
    public function track($trackingNumber)
    {
        $complaint = Complaint::where('tracking_number', $trackingNumber)
            ->with(['category', 'area', 'images', 'updates' => function($query) {
                $query->where('is_public', true)->latest();
            }])
            ->first();

        if (!$complaint) {
            return response()->json([
                'success' => false,
                'message' => 'رقم التتبع غير صحيح'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $complaint
        ]);
    }

    /**
     * عرض الشكاوي على الخريطة
     */
    public function map(Request $request)
    {
        $query = Complaint::with(['category', 'area'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // فلترة حسب الحالة
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب التصنيف
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // فلترة حسب المنطقة
        if ($request->has('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        $complaints = $query->get();

        return response()->json([
            'success' => true,
            'data' => $complaints
        ]);
    }
}
