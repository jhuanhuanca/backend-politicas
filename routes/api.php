<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DebateModerationController;
use App\Http\Controllers\Api\Admin\DeviceRecoveryController;
use App\Http\Controllers\Api\Admin\KpiController;
use App\Http\Controllers\Api\Admin\LeaderController;
use App\Http\Controllers\Api\Admin\ReportQueueController;
use App\Http\Controllers\Api\Admin\SanctionController;
use App\Http\Controllers\Api\Admin\StaffAuthController;
use App\Http\Controllers\Api\Admin\StatusController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\ActivityNoticeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\DebateController;
use App\Http\Controllers\Api\V1\MetricsController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\OpenApiController;
use App\Http\Controllers\Api\V1\ParticipationController;
use App\Http\Controllers\Api\V1\PollController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/openapi.json', [OpenApiController::class, 'show'])->middleware('throttle:60,1,openapi');

    Route::get('/ping', function () {
        return response()->json([
            'data' => ['status' => 'ok'],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    });

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1,register');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1,login');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'device', 'throttle:30,1,logout']);
    });

    Route::get('/debates', [DebateController::class, 'index'])->middleware('throttle:60,1,debates');
    Route::get('/debates/newer', [DebateController::class, 'newer'])->middleware('throttle:120,1,debates-newer');
    Route::get('/debates/{topic}', [DebateController::class, 'show'])->middleware('throttle:60,1,debate');
    Route::get('/debates/{topic}/updates', [DebateController::class, 'updates'])->middleware('throttle:120,1,debate-updates');
    Route::get('/arguments/newer', [DebateController::class, 'newerArguments'])->middleware('throttle:120,1,arguments-newer');
    Route::get('/news', [NewsController::class, 'index'])->middleware('throttle:60,1,news');
    Route::get('/news/{newsItem}', [NewsController::class, 'show'])->middleware('throttle:60,1,news-item');
    Route::get('/polls', [PollController::class, 'index'])->middleware('throttle:60,1,polls');
    Route::get('/metrics', [MetricsController::class, 'show'])->middleware('throttle:60,1,metrics');
    Route::get('/search', [SearchController::class, 'index'])->middleware('throttle:60,1,search');

    Route::middleware(['auth:sanctum', 'device', 'throttle:120,1,conversaciones'])->group(function (): void {
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
    });

    Route::middleware(['auth:sanctum', 'device', 'open', 'throttle:30,1,mensajes'])->group(function (): void {
        Route::post('/conversations', [ConversationController::class, 'store']);
        Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'send']);
    });

    Route::middleware(['auth:sanctum', 'device', 'throttle:30,1,acciones'])->group(function (): void {
        Route::middleware('open')->group(function (): void {
            Route::post('/debates', [DebateController::class, 'store']);
            Route::post('/debates/{topic}/arguments', [DebateController::class, 'storeArgument']);
            Route::post('/debates/{topic}/reactions', [DebateController::class, 'react']);
            Route::post('/debates/{topic}/shares', [DebateController::class, 'share']);
            Route::post('/arguments/{argument}/reactions', [DebateController::class, 'reactToArgument']);
            Route::post('/arguments/{argument}/votes', [DebateController::class, 'vote']);
            Route::post('/reports', [ReportController::class, 'store']);
            Route::post('/polls/{poll}/votes', [PollController::class, 'vote']);
        });
        Route::get('/me/standing', [AccountController::class, 'standing']);
        Route::delete('/me', [AccountController::class, 'destroy']);
        Route::get('/me/notices', [ActivityNoticeController::class, 'index']);
        Route::post('/me/notices/{notice}/read', [ActivityNoticeController::class, 'read']);
        Route::get('/me/participation', [ParticipationController::class, 'show']);
        Route::post('/me/participation/consent', [ParticipationController::class, 'consent']);
    });
});

Route::prefix('admin')->middleware('admin.header')->group(function (): void {
    Route::post('/auth/login', [StaffAuthController::class, 'login'])->middleware('throttle:10,1,admin-login');
    Route::post('/auth/totp', [StaffAuthController::class, 'confirmTotp'])->middleware('throttle:10,1,admin-totp');

    Route::middleware(['auth:sanctum', 'admin', 'throttle:30,1,admin'])->group(function (): void {
        Route::get('/me', [StaffAuthController::class, 'me']);
        Route::post('/auth/logout', [StaffAuthController::class, 'logout']);
        Route::get('/dashboard', [DashboardController::class, 'show']);
        Route::post('/users/{nick}/release-device', [DeviceRecoveryController::class, 'release'])
            ->where('nick', '[A-Za-z0-9_]+');
        Route::get('/status', [StatusController::class, 'show']);
        Route::get('/kpis', [KpiController::class, 'show']);
        Route::get('/leaders', [LeaderController::class, 'index']);
        Route::post('/leaders/{leaderCandidate}/decision', [LeaderController::class, 'decide']);
        Route::get('/debates', [DebateModerationController::class, 'index']);
        Route::post('/debates/{topic}/state', [DebateModerationController::class, 'update']);
        Route::get('/reports', [ReportQueueController::class, 'index']);
        Route::get('/reports/newer', [ReportQueueController::class, 'newer']);
        Route::post('/reports/{report}/decision', [ReportQueueController::class, 'decide']);
        Route::post('/reports/{report}/sanction', [SanctionController::class, 'store']);
        Route::get('/sanctions', [SanctionController::class, 'index']);
        Route::post('/sanctions/{sanction}/lift', [SanctionController::class, 'lift']);
    });
});
