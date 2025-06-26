<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WordRequestForm;
use App\Models\Words;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Illuminate\Http\Request;

class WordsController extends Controller
{
    public function index(Request $request)
    {
        $query = Words::with(['translations' => function ($query) {
            $query->whereIn('language', ['ru', 'uz']);
        }]);

        if ($request->has('language')) {
            $query->where('language', $request->input('language'));
        }

        if ($request->has('word')) {
            $query->where('word', 'like', '%'.$request->input('word').'%');
        }

        $words = $query->orderBy('id', 'desc')->paginate(100);

        return Inertia::render('Admin/Words/Index', [
            'words' => $words,
            'filters' => $request->only(['language','word']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Words/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WordRequestForm $request)
    {
        $validatedData = $request->validated();
        $validatedData['translate'] = 'none';
        $validatedData['length'] = strlen($validatedData['word']);
        $translations = $validatedData['translations'] ?? [];
        $sentences = $validatedData['sentences'] ?? [];
        unset($validatedData['translations']);
        unset($validatedData['sentences']);

        $word = Words::create($validatedData);

        // Save translations if provided
        if (!empty($translations)) {
            foreach ($translations as $language => $translation) {
                if (!empty($translation)) {
                    $word->translations()->create([
                        'language' => $language,
                        'translation' => $translation
                    ]);
                }
            }
        }

        // Save sentences if provided
        if (!empty($sentences)) {
            foreach ($sentences as $sentenceData) {
                if (!empty($sentenceData['content'])) {
                    $sentence = $word->sentences()->create([
                        'content' => $sentenceData['content'],
                        'content_translate' => $sentenceData['content_translate'] ?? '',
                        'words_id' => $word->id
                    ]);

                    // Save sentence translations if provided
                    if (!empty($sentenceData['translations'])) {
                        foreach ($sentenceData['translations'] as $translationData) {
                            if (!empty($translationData['translation'])) {
                                $sentence->translations()->create([
                                    'word_sentence_id' => $sentence->id,
                                    'language' => $translationData['language'],
                                    'translation' => $translationData['translation']
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return redirect()->route('admin.words.index')->with('success', 'Word created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Words $word)
    {
        return Inertia::render('Admin/Words/Show', [
            'word' => $word,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Words $word)
    {
        $word->load(['translations' => function ($query) {
            $query->whereIn('language', ['ru', 'uz']);
        }]);

        $word->load(['sentences.translations' => function ($query) {
            // Load all sentence translations
        }]);

        return Inertia::render('Admin/Words/Edit', [
            'word' => $word,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WordRequestForm $request, Words $word)
    {
        $validatedData = $request->validated();
        $translations = $validatedData['translations'] ?? [];
        $sentences = $validatedData['sentences'] ?? [];
        unset($validatedData['translations']);
        unset($validatedData['sentences']);

        $word->update($validatedData);

        // Update translations
        if (!empty($translations)) {
            foreach ($translations as $language => $translation) {
                if (!empty($translation)) {
                    $word->translations()->updateOrCreate(
                        ['language' => $language],
                        ['translation' => $translation]
                    );
                }
            }
        }

        // Handle sentences
        // First, get existing sentence IDs to track which ones to keep
        $existingSentenceIds = $word->sentences()->pluck('id')->toArray();
        $updatedSentenceIds = [];

        if (!empty($sentences)) {
            foreach ($sentences as $sentenceData) {
                if (!empty($sentenceData['content'])) {
                    // If sentence has an ID, update it, otherwise create new
                    if (isset($sentenceData['id'])) {
                        $sentence = $word->sentences()->find($sentenceData['id']);
                        if ($sentence) {
                            $sentence->update([
                                'content' => $sentenceData['content'],
                                'content_translate' => $sentenceData['content_translate'] ?? '',
                            ]);
                            $updatedSentenceIds[] = $sentence->id;
                        }
                    } else {
                        $sentence = $word->sentences()->create([
                            'content' => $sentenceData['content'],
                            'content_translate' => $sentenceData['content_translate'] ?? '',
                            'words_id' => $word->id
                        ]);
                        $updatedSentenceIds[] = $sentence->id;
                    }

                    // Handle sentence translations
                    if (!empty($sentenceData['translations'])) {
                        // Get existing translation IDs to track which ones to keep
                        $existingTranslationIds = $sentence->translations()->pluck('id')->toArray();
                        $updatedTranslationIds = [];

                        foreach ($sentenceData['translations'] as $translationData) {
                            if (!empty($translationData['translation'])) {
                                // If translation has an ID, update it, otherwise create new
                                if (isset($translationData['id'])) {
                                    $translation = $sentence->translations()->find($translationData['id']);
                                    if ($translation) {
                                        $translation->update([
                                            'language' => $translationData['language'],
                                            'translation' => $translationData['translation']
                                        ]);
                                        $updatedTranslationIds[] = $translation->id;
                                    }
                                } else {
                                    $translation = $sentence->translations()->create([
                                        'word_sentence_id' => $sentence->id,
                                        'language' => $translationData['language'],
                                        'translation' => $translationData['translation']
                                    ]);
                                    $updatedTranslationIds[] = $translation->id;
                                }
                            }
                        }

                        // Delete translations that weren't updated
                        $translationsToDelete = array_diff($existingTranslationIds, $updatedTranslationIds);
                        if (!empty($translationsToDelete)) {
                            $sentence->translations()->whereIn('id', $translationsToDelete)->delete();
                        }
                    }
                }
            }
        }

        // Delete sentences that weren't updated
        $sentencesToDelete = array_diff($existingSentenceIds, $updatedSentenceIds);
        if (!empty($sentencesToDelete)) {
            $word->sentences()->whereIn('id', $sentencesToDelete)->delete();
        }

        return redirect()->route('admin.words.index')->with('success', 'Word updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Words $word)
    {
        $word->delete();

        return redirect()->route('admin.words.index')->with('success', 'Word deleted successfully.');
    }

    public function updateTranslation(Request $request, Words $word): RedirectResponse
    {
        $request->validate([
            'language' => 'required|in:ru,uz',
            'translation' => 'required|string|max:512',
        ]);

        $translation = $word->translations()->updateOrCreate(
            ['language' => $request->input('language')],
            ['translation' => $request->input('translation')]
        );

        return back(303)->with('success', 'Translation updated successfully.');
    }

    public function updateDifficulty(Request $request, Words $word): RedirectResponse
    {
        $request->validate([
            'difficulty_level' => 'required|integer',
        ]);

        $word->difficulty_level = $request->input('difficulty_level');
        $word->save();

        return back(303)->with('success', 'Difficulty level updated successfully.');
    }
}
