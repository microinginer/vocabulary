<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWordSentencesRequest;
use App\Http\Requests\StoreWordsRequest;
use App\Http\Requests\UpdateWordsRequest;
use App\Models\Words;
use App\Models\WordSentences;
use Inertia\Inertia;

class WordsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $words = Words::query()
//            ->where('is_active', true)
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
     * Store a newly created resource in storage.
     */
    public function store(StoreWordsRequest $request)
    {
        $model = new Words();
        $model->fill($request->all());
        $model->save();

        return to_route('words');
    }

    /**
     * Display the specified resource.
     */
    public function show(Words $words)
    {
        return Inertia::render('Words/Show', [
            'words' => $words,
            'sentences' => $words->getSentences()->get(),
        ]);
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
     * Update the specified resource in storage.
     */
    public function update(UpdateWordsRequest $request, Words $words)
    {
        $words->fill($request->all());
        $words->save();

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

    public function sentenceStore(Words $words, StoreWordSentencesRequest $sentencesRequest)
    {
        $model = new WordSentences();
        $model->words_id = $words->id;
        $model->fill($sentencesRequest->all());
        $model->save();

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
