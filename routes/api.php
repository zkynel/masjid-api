<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\MosqueOnboardingController;
use App\Http\Controllers\Api\V1\MosqueProfileController;
use App\Http\Controllers\Api\V1\PrayerScheduleController;
use App\Http\Controllers\Api\V1\RegionController;

use App\Http\Controllers\Api\V1\AdminVerificationController;

Route::prefix('v1')->group(function () {

    // AUTH
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // PUBLIC
    Route::get('/public/{slug}/posts', [PublicController::class, 'postsBySlug']);
    Route::get('/public/{slug}/posts/{postSlug}', [PublicController::class, 'postDetailBySlug']);
    Route::get('/public/{slug}/profile', [PublicController::class, 'profileBySlug']);
            
    Route::get('/public/{slug}/prayer/provinces', [PrayerScheduleController::class, 'provinces']);
    Route::post('/public/{slug}/prayer/cities', [PrayerScheduleController::class, 'cities']);
    Route::post('/public/{slug}/prayer/schedule', [PrayerScheduleController::class, 'schedule']);

    // PROTECTED
    Route::middleware('auth:sanctum')->group(function () {

        // ONBOARDING (domain, template, terms, verification)
        Route::prefix('onboarding')->group(function () {
            Route::post('/domain/check', [MosqueOnboardingController::class, 'checkDomain']);
            Route::post('/domain/set', [MosqueOnboardingController::class, 'setDomain']);

            Route::get('/templates', [MosqueOnboardingController::class, 'listTemplates']);
            Route::post('/template/select', [MosqueOnboardingController::class, 'selectTemplate']);

            Route::post('/terms/accept', [MosqueOnboardingController::class, 'acceptTerms']);

            Route::post('/verification/submit', [MosqueOnboardingController::class, 'submitVerification']);
            Route::get('/verification/status', [MosqueOnboardingController::class, 'verificationStatus']);
        });

        // PROFILE MASJID (by slug)
        Route::get('/mosques/{slug}/profile', [MosqueProfileController::class, 'show']);
        Route::put('/mosques/{slug}/profile', [MosqueProfileController::class, 'update']);
        Route::post('/mosques/{slug}/documents', [MosqueProfileController::class, 'uploadDocuments']);
        Route::post('/mosques/{slug}/profile-image', [MosqueProfileController::class, 'uploadProfileImage']);
        Route::delete('/mosques/{slug}/profile-image', [MosqueProfileController::class, 'deleteProfileImage']);

        // POSTS (by mosque slug)
        Route::get('/mosques/{slug}/posts', [PostController::class, 'index']);
        Route::post('/mosques/{slug}/posts', [PostController::class, 'store']);
        Route::get('/mosques/{slug}/posts/{postId}', [PostController::class, 'show']);
        Route::put('/mosques/{slug}/posts/{postId}', [PostController::class, 'update']);
        Route::delete('/mosques/{slug}/posts/{postId}', [PostController::class, 'destroy']);

        Route::post('/mosques/{slug}/posts/{postId}/publish', [PostController::class, 'publish']);
        Route::post('/mosques/{slug}/posts/{postId}/unpublish', [PostController::class, 'unpublish']);
        Route::post('/mosques/{slug}/posts/{postId}/gallery', [PostController::class, 'addGalleryImages']);
        Route::delete('/mosques/{slug}/posts/{postId}/gallery', [PostController::class, 'removeGalleryImage']);
    });

    // SUPER ADMIN (protected + role check)
    Route::middleware(['auth:sanctum', 'super_admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminVerificationController::class, 'dashboard']);
        Route::get('/verifications', [AdminVerificationController::class, 'index']);
        Route::get('/verifications/{mosqueId}', [AdminVerificationController::class, 'show']);
        Route::post('/verifications/{mosqueId}/approve', [AdminVerificationController::class, 'approve']);
        Route::post('/verifications/{mosqueId}/reject', [AdminVerificationController::class, 'reject']);
    });

    Route::prefix('regions')->group(function () {
        Route::get('/provinces', [RegionController::class, 'provinces']);
        Route::get('/regencies/{provinceId}', [RegionController::class, 'regencies']);
        Route::get('/districts/{regencyId}', [RegionController::class, 'districts']);
        Route::get('/villages/{districtId}', [RegionController::class, 'villages']);
    });

});

