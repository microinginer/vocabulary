<?php

namespace App\Http\Controllers;

use App\Http\Requests\WordSentencesFormRequest;
use App\Http\Requests\WordRequestForm;
use App\Models\Words;
use App\Models\WordSentences;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class WordsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $words = Words::query()
            ->paginate();

        return Inertia::render('Words/Index', [
            'words' => $words,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Words/Create');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Words $words)
    {
        return Inertia::render('Words/Update', [
            'words' => $words,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Words $words)
    {
        return Inertia::render('Words/Show', [
            'words' => $words,
            'sentences' => $words->sentences,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WordRequestForm $request)
    {
        $request->save(new Words());

        return to_route('words');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WordRequestForm $request, Words $words): RedirectResponse
    {
        $request->save($words);

        return to_route('words');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Words $words)
    {
        $words->delete();

        return to_route('words');
    }

    public function sentenceStore(Words $words, WordSentencesFormRequest $sentencesRequest)
    {
        $model = new WordSentences();
        $model->words_id = $words->id;
        $sentencesRequest->save($model);

        return to_route('words.show', $words);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function sentenceDestroy(WordSentences $wordSentences)
    {
        $wordSentences->delete();

        return to_route('words.show', Words::query()->where('id', $wordSentences->words_id)->first());
    }
}
