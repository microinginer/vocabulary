<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class VocabExportCoreSeed extends Command
{
    protected $signature = 'vocab:export-core
        {--lang=en : Source language from words.language}
        {--only-active=1 : Use only words.is_active=1 (1/0)}
        {--out=seed : Output directory relative to project root}
        {--with-audio-ref=0 : Generate pronunciationRef like audio/en/a1/apple.mp3 (1/0)}';

    protected $description = 'Export MySQL vocab into seed JSON files compatible with npm run import:vocab';

    public function handle(): int
    {
        $sourceLang = (string) $this->option('lang');
        $onlyActive = (int) $this->option('only-active') === 1;
        $outDir = base_path((string) $this->option('out'));
        $withAudioRef = (int) $this->option('with-audio-ref') === 1;

        File::ensureDirectoryExists($outDir);

        $words = DB::table('words')
            ->select(['id', 'word', 'language', 'pronunciation', 'difficulty_level', 'is_active'])
            ->when($onlyActive, fn ($q) => $q->where('is_active', 1))
            ->where('language', $sourceLang)
            ->orderBy('id')
            ->get();

        if ($words->isEmpty()) {
            $this->warn('No words found for export.');
            return self::SUCCESS;
        }

        $wordIds = $words->pluck('id')->map(fn ($v) => (int) $v)->all();

        // Translations (word_translations)
        $translations = DB::table('word_translations')
            ->select(['word_id', 'language', 'translation'])
            ->whereIn('word_id', $wordIds)
            ->get();

        $tMap = [];
        foreach ($translations as $t) {
            $wid = (int) $t->word_id;
            $lang = (string) $t->language;
            $val = trim((string) $t->translation);

            if ($val === '') {
                continue;
            }

            $tMap[$wid] ??= [];
            $tMap[$wid][$lang] = $val;
        }

        // Sentences (word_sentences) -> pick best sentence for exampleSentenceEn
        $sentences = DB::table('word_sentences')
            ->select(['id', 'words_id', 'content'])
            ->whereIn('words_id', $wordIds)
            ->orderBy('id')
            ->get();

        $sentenceMap = []; // words_id => sentence
        foreach ($sentences as $s) {
            $wid = (int) $s->words_id;
            $content = trim((string) $s->content);

            if ($content === '') {
                continue;
            }

            // choose shortest sentence
            if (!isset($sentenceMap[$wid]) || mb_strlen($content) < mb_strlen($sentenceMap[$wid])) {
                $sentenceMap[$wid] = $content;
            }
        }

        $sets = [];

        foreach ($words as $w) {
            $mysqlId = (int) $w->id;
            $word = trim((string) $w->word);
            if ($word === '') {
                continue;
            }

            $level = $this->toCefr((int) ($w->difficulty_level ?? 0));
            if ($level === null) {
                continue;
            }

            $trs = $tMap[$mysqlId] ?? [];
            if (count($trs) === 0) {
                continue;
            }

            $wordId = strtolower($level) . '_' . Str::slug($word, '_');
            $setId = 'core_' . strtolower($level);

            $sets[$setId] ??= [
                'set' => [
                    'id' => $setId,
                    'title' => $level . ' Core',
                    'level' => $level,
                    'description' => 'Foundational English vocabulary for beginners.',
                ],
                'words' => [],
            ];

            $ipa = $w->pronunciation ? trim((string) $w->pronunciation) : null;

            $pronunciationRef = null;
            if ($withAudioRef) {
                $pronunciationRef = 'audio/' . $sourceLang . '/' . strtolower($level) . '/' . Str::slug($word, '_') . '.mp3';
            }

            $example = $sentenceMap[$mysqlId] ?? $this->defaultExample($word);

            $sets[$setId]['words'][$wordId] = [
                'id' => $wordId,
                'word' => $word,
                'ipa' => $ipa ?: null,
                'pronunciationRef' => $pronunciationRef,
                'exampleSentenceEn' => $example,
                'translations' => $trs,
                'tags' => [],
                'level' => $level,
            ];
        }

        if (count($sets) === 0) {
            $this->warn('Nothing to export (no words with translations/levels).');
            return self::SUCCESS;
        }

        foreach ($sets as $setId => $payload) {
            $payload['words'] = array_values($payload['words']);

            $fileName = 'vocab_' . $setId . '.json'; // ex: vocab_core_a1.json
            $filePath = $outDir . DIRECTORY_SEPARATOR . $fileName;

            File::put(
                $filePath,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
            );

            $this->info("Generated {$fileName} (" . count($payload['words']) . " words)");
        }

        $this->info('Done ✅ Now run: npm run import:vocab');
        return self::SUCCESS;
    }

    private function toCefr(int $difficulty): ?string
    {
        return match ($difficulty) {
            1 => 'A1',
            2 => 'B1',
            3 => 'C1',
            default => null,
        };
    }

    private function defaultExample(string $word): string
    {
        $w = trim($word);
        return 'I learn the word "' . $w . '".';
    }
}
