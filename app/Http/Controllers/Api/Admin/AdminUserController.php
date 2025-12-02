<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * قائمة المستخدمين
     */
    public function index(Request $request)
    {
        $query = User::query()->with('category');

        // فلترة حسب الدور
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // بحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * إنشاء مستخدم جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', new \App\Rules\NoProfanity],
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => ['required', 'string', 'max:20', 'unique:users', 'regex:/^(09\d{8}|(\+963|00963)9\d{8})$/'],
            'password' => 'required|string|min:6',
            'role' => 'required|in:citizen,employee,admin',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'category_id' => $request->role === 'employee' ? $request->category_id : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المستخدم بنجاح',
            'data' => $user
        ], 201);
    }

    /**
     * تفاصيل مستخدم
     */
    public function show($id)
    {
        $user = User::withCount(['complaints', 'assignments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * تحديث مستخدم
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255', new \App\Rules\NoProfanity],
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone,' . $id, 'regex:/^(09\d{8}|(\+963|00963)9\d{8})$/'],
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:citizen,employee,admin',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'role']);
        
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }
        
        if ($request->role === 'employee' && $request->has('category_id')) {
            $data['category_id'] = $request->category_id;
        } else if ($request->role !== 'employee') {
            // If role changes from employee to something else, clear category_id
            $data['category_id'] = null;
        }


        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح',
            'data' => $user
        ]);
    }

    /**
     * حذف مستخدم
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // منع حذف نفسك
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف حسابك الخاص'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح'
        ]);
    }
}
