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

        $words = $query->paginate(100);

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
        Words::create($request->validated());

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
        return Inertia::render('Admin/Words/Edit', [
            'word' => $word,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WordRequestForm $request, Words $word)
    {
        $word->update($request->validated());

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
