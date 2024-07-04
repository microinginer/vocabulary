<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\UserChallengeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\UserWordMarkController;
use App\Http\Controllers\Api\WordsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomWordListController;
use App\Http\Controllers\Api\CustomWordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', UserProfileController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/challenges', [ChallengeController::class, 'index']);
    Route::get('/user-challenges', [UserChallengeController::class, 'index']);
    Route::post('/user-challenges/progress', [UserChallengeController::class, 'updateProgress']);
    Route::put('challenges/update-progress', [ChallengeController::class, 'updateProgressByMetrics']);
    Route::post('/user-challenges/add', [UserChallengeController::class, 'addChallenge']);
    Route::delete('/user-challenges/{id}', [UserChallengeController::class, 'removeChallenge']);

    Route::get('/word-marks', [UserWordMarkController::class, 'index']);
    Route::post('/word-marks', [UserWordMarkController::class, 'store']);
});

Route::get('/online-users', [UserController::class, 'getOnlineUsers'])->middleware('auth:sanctum');
Route::get('/all-users', [UserController::class, 'getAllUsers'])->middleware('auth:sanctum');

Route::get('random-words', [WordsController::class, 'getRandomWords']);
Route::get('random-sentences', [WordsController::class, 'getRandomSentences']);


Route::middleware('auth:sanctum')->group(function () {
    // Routes for custom word lists
    Route::get('custom-word-lists', [CustomWordListController::class, 'index']);
    Route::post('custom-word-lists', [CustomWordListController::class, 'store']);
    Route::get('custom-word-lists/{id}', [CustomWordListController::class, 'show']);
    Route::put('custom-word-lists/{id}', [CustomWordListController::class, 'update']);
    Route::delete('custom-word-lists/{id}', [CustomWordListController::class, 'destroy']);

    // Routes for custom words within a specific list
    Route::get('custom-word-lists/{listId}/words', [CustomWordController::class, 'index']);
    Route::post('custom-word-lists/{listId}/words', [CustomWordController::class, 'store']);
    Route::get('custom-word-lists/{listId}/words/{id}', [CustomWordController::class, 'show']);
    Route::put('custom-word-lists/{listId}/words/{id}', [CustomWordController::class, 'update']);
    Route::delete('custom-word-lists/{listId}/words/{id}', [CustomWordController::class, 'destroy']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/game', [GameController::class, 'createGame']);
    Route::post('/game/{sessionId}/accept', [GameController::class, 'acceptGame']);
    Route::post('/game/{sessionId}/decline', [GameController::class, 'declineGame']);
    Route::get('/games/active', [GameController::class, 'getActiveSessions']);
    Route::get('/games/history', [GameController::class, 'getGameHistory']);
    Route::get('/game/{session}', [GameController::class, 'resultGame']);
    Route::get('/games', [GameController::class, 'getUserGames']);
});
