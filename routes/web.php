<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestPageController;
use App\Http\Controllers\WordsController;
use App\Models\GameSession;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $user = \App\Models\User::query()->where('id', 3)->first();
    $activeSession = GameSession::query()
        ->where(function ($query) use ($user) {
            $query->where('player1_id', $user->id)
                ->orWhere('player2_id', $user->id);
        })->whereIn('status', ['pending','active'])
                ->first()
    ;

    dd($activeSession);


    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::get('/ws', [TestPageController::class, 'ws']);
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/test-page', [TestPageController::class, 'index'])->middleware(['auth', 'verified'])->name('test-page');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/words', [WordsController::class, 'index'])->name('words');
    Route::get('/words/create', [WordsController::class, 'create'])->name('words.create');
    Route::get('/words/{words}/edit', [WordsController::class, 'edit'])->name('words.edit');
    Route::get('/words/{words}/delete', [WordsController::class, 'destroy'])->name('words.delete');
    Route::get('/words/{words}/show', [WordsController::class, 'show'])->name('words.show');

    Route::post('/words/create', [WordsController::class, 'store'])->name('words.store');
    Route::post('/words/{words}/update', [WordsController::class, 'update'])->name('words.update');
    Route::post('/words/{words}/update', [WordsController::class, 'update'])->name('words.update');

    Route::post('/words/sentence/{words}/create', [WordsController::class, 'sentenceStore'])->name('words.sentences.store');
    Route::get('/words/{wordSentences}/sentenceDelete', [WordsController::class, 'sentenceDestroy'])->name('words.sentences.delete');
});

require __DIR__ . '/auth.php';
