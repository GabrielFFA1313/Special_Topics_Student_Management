<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProgramController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\AcademicTermController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\CourseOfferingController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\GradeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::apiResource('programs', ProgramController::class);
        Route::apiResource('courses', CourseController::class);
        Route::apiResource('academic-terms', AcademicTermController::class);
        Route::apiResource('students', StudentController::class);
        Route::apiResource('course-offerings', CourseOfferingController::class);

        Route::get('/enrollments', [EnrollmentController::class, 'index']);
        Route::post('/enrollments', [EnrollmentController::class, 'store']);
        Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show']);
        Route::patch('/enrollments/{enrollment}', [EnrollmentController::class, 'update']);
        Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);

        Route::get('/students/{student}/enrollments', [EnrollmentController::class, 'forStudent']);
        Route::get('/course-offerings/{courseOffering}/students', [EnrollmentController::class, 'forCourseOffering']);

        Route::get('/grades', [GradeController::class, 'index']);
        Route::post('/grades', [GradeController::class, 'store']);
        Route::get('/grades/{grade}', [GradeController::class, 'show']);
        Route::put('/grades/{grade}', [GradeController::class, 'update']);
        Route::patch('/grades/{grade}', [GradeController::class, 'update']);

        Route::get('/students/{student}/grades', [GradeController::class, 'forStudent']);
        Route::get('/students/{student}/academic-record', [StudentController::class, 'academicRecord']);
    });

});