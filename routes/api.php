<?php

use App\Http\Controllers\Api\Admin\AdminComplaintController;
use App\Http\Controllers\Api\Admin\AdminStatisticsController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Employee\EmployeeComplaintController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ComplaintController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (بدون تسجيل دخول)
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:60,1'])->group(function () {
    // الشكاوي العامة
    Route::prefix('complaints')->group(function () {
        Route::post('/', [ComplaintController::class, 'store']); // تقديم شكوى
        Route::get('/map', [ComplaintController::class, 'map']); // الشكاوي على الخريطة
        Route::get('/{trackingNumber}', [ComplaintController::class, 'track']); // متابعة شكوى
    });

    // التصنيفات
    Route::get('categories', [CategoryController::class, 'index']);
    // المناطق
    Route::get('areas', [\App\Http\Controllers\Api\AreaController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Auth Routes (التسجيل والدخول)
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        // محمية (تحتاج تسجيل دخول)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (تحتاج تسجيل دخول)
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        // Profile Routes
        Route::prefix('profile')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\Api\ProfileController::class, 'stats']);
            Route::put('/update', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
            Route::put('/password', [\App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
            Route::post('/avatar', [\App\Http\Controllers\Api\ProfileController::class, 'uploadAvatar']);
            Route::get('/notifications', [\App\Http\Controllers\Api\ProfileController::class, 'notifications']);
            Route::post('/notifications/read', [\App\Http\Controllers\Api\ProfileController::class, 'markNotificationsAsRead']);
        });

        /*
        |--------------------------------------------------------------------------
        | Employee Routes (للموظفين)
        |--------------------------------------------------------------------------
        */
        Route::middleware('employee')->prefix('employee')->group(function () {
            Route::get('/complaints', [EmployeeComplaintController::class, 'index']); // الشكاوى المعينة
            Route::get('/complaints/{id}', [EmployeeComplaintController::class, 'show']); // تفاصيل شكوى
            Route::post('/complaints/{id}/start', [EmployeeComplaintController::class, 'startWork']); // بدء العمل
            Route::post('/complaints/{id}/complete', [EmployeeComplaintController::class, 'completeWork']); // إنهاء العمل
            Route::post('/complaints/{id}/update', [EmployeeComplaintController::class, 'addUpdate']); // إضافة تحديث
            Route::get('/statistics', [EmployeeComplaintController::class, 'statistics']); // إحصائيات الموظف
        });

        /*
        |--------------------------------------------------------------------------
        | Admin Routes (للمدير)
        |--------------------------------------------------------------------------
        */
        Route::prefix('admin')->group(function () {

            // إدارة الشكاوي
            Route::prefix('complaints')->group(function () {
                Route::get('/', [AdminComplaintController::class, 'index']); // قائمة الشكاوي
                Route::get('/{id}', [AdminComplaintController::class, 'show']); // تفاصيل شكوى
                Route::put('/{id}/status', [AdminComplaintController::class, 'updateStatus']); // تحديث الحالة
                Route::put('/{id}/priority', [AdminComplaintController::class, 'updatePriority']); // تحديث الأولوية
                Route::post('/{id}/assign', [AdminComplaintController::class, 'assign']); // تعيين لموظف
                Route::post('/{id}/update', [AdminComplaintController::class, 'addUpdate']); // إضافة تحديث
                Route::delete('/{id}', [AdminComplaintController::class, 'destroy']); // حذف
            });

            // الإحصائيات
            Route::prefix('statistics')->group(function () {
                Route::get('overview', [AdminStatisticsController::class, 'overview']);
                Route::get('by-category', [AdminStatisticsController::class, 'byCategory']);
                Route::get('by-area', [AdminStatisticsController::class, 'byArea']);
                Route::get('by-status', [AdminStatisticsController::class, 'byStatus']);
                Route::get('trends', [AdminStatisticsController::class, 'trends']);
            });

            // إدارة المستخدمين (Admin فقط)
            Route::middleware('admin')->prefix('users')->group(function () {
                Route::get('/', [AdminUserController::class, 'index']);
                Route::post('/', [AdminUserController::class, 'store']);
                Route::get('/{id}', [AdminUserController::class, 'show']);
                Route::put('/{id}', [AdminUserController::class, 'update']);
                Route::delete('/{id}', [AdminUserController::class, 'destroy']);
            });

            // إدارة التصنيفات (Admin فقط)
            Route::middleware('admin')->prefix('categories')->group(function () {
                Route::post('/', [AdminCategoryController::class, 'store']);
                Route::put('/{id}', [AdminCategoryController::class, 'update']);
                Route::delete('/{id}', [AdminCategoryController::class, 'destroy']);
            });
        });
    });
});
