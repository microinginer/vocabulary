<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordSentences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\ResponseFactory;

class WordSentencesController extends Controller
{
    public function index(): Response|ResponseFactory
    {
        $wordSentences = WordSentences::with('translations')->paginate(100);
        return inertia('Admin/WordSentences/Index', ['wordSentences' => $wordSentences]);
    }

    public function updateTranslation(Request $request, WordSentences $wordSentence): RedirectResponse
    {
        $request->validate([
            'language' => 'required|in:ru,uz,az',
            'translation' => 'required|string',
        ]);

        $wordSentence->translations()->updateOrCreate(
            ['language' => $request->input('language')],
            ['translation' => $request->input('translation')]
        );

        return back(303)->with('success', 'Translation updated successfully.');
    }
}
