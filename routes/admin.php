<?php

use App\Http\Controllers\Admin\WordsController;
use App\Http\Controllers\Admin\WordSentencesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', function () {
        return view('admin.dashboard');
    });
    Route::put('/words/{word}/translation', [WordsController::class, 'updateTranslation']);
    Route::put('/words/{word}/difficulty', [WordsController::class, 'updateDifficulty']);

    Route::resource('words', WordsController::class)->names([
        'index' => 'admin.words.index',
        'create' => 'admin.words.create',
        'store' => 'admin.words.store',
        'show' => 'admin.words.show',
        'edit' => 'admin.words.edit',
        'update' => 'admin.words.update',
        'destroy' => 'admin.words.destroy',
    ]);

    Route::put('/word-sentences/{wordSentence}/translation', [WordSentencesController::class, 'updateTranslation']);
    Route::get('/word-sentences', [WordSentencesController::class, 'index'])->name('admin.word-sentences.index');
});
