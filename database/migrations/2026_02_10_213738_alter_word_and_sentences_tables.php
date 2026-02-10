<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->string('gpt_status', 20)->default('new')->after('is_active');
            $table->dateTime('gpt_enriched_at')->nullable()->after('gpt_status');
            $table->unsignedTinyInteger('gpt_attempts')->default(0)->after('gpt_enriched_at');
            $table->text('gpt_last_error')->nullable()->after('gpt_attempts');
            $table->dateTime('gpt_lock_until')->nullable()->after('gpt_last_error');
            $table->string('gpt_model', 50)->nullable()->after('gpt_lock_until');

            $table->index(['gpt_status', 'gpt_lock_until']);
            $table->index(['gpt_enriched_at']);
        });

        Schema::table('word_sentences', function (Blueprint $table) {
            $table->string('gpt_status', 20)->default('new')->after('content_translate');
            $table->dateTime('gpt_enriched_at')->nullable()->after('gpt_status');
            $table->unsignedTinyInteger('gpt_attempts')->default(0)->after('gpt_enriched_at');
            $table->text('gpt_last_error')->nullable()->after('gpt_attempts');
            $table->dateTime('gpt_lock_until')->nullable()->after('gpt_last_error');
            $table->string('gpt_model', 50)->nullable()->after('gpt_lock_until');

            $table->index(['gpt_status', 'gpt_lock_until']);
        });

        Schema::table('words', function (Blueprint $table) {
            $table->index(
                ['gpt_status', 'gpt_lock_until'],
                'idx_words_gpt_status_lock'
            );

            $table->index(
                ['gpt_enriched_at'],
                'idx_words_gpt_enriched_at'
            );
        });
        Schema::table('word_sentences', function (Blueprint $table) {
            $table->index(
                ['gpt_status', 'gpt_lock_until'],
                'idx_word_sentences_gpt_status_lock'
            );
        });
        Schema::table('word_translations', function (Blueprint $table) {
            $table->unique(
                ['word_id', 'language'],
                'uq_word_translations_word_language'
            );
        });
        Schema::table('word_sentence_translations', function (Blueprint $table) {
            $table->unique(
                ['word_sentence_id', 'language'],
                'uq_word_sentence_translations_sentence_language'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('word_sentence_translations', function (Blueprint $table) {
            $table->unique(
                ['word_sentence_id', 'language'],
                'uq_word_sentence_translations_sentence_language'
            );
        });

        Schema::table('word_translations', function (Blueprint $table) {
            $table->dropUnique('uq_word_translations_word_language');
        });

        Schema::table('word_sentences', function (Blueprint $table) {
            $table->dropIndex('idx_word_sentences_gpt_status_lock');
        });
        Schema::table('words', function (Blueprint $table) {
            $table->dropIndex('idx_words_gpt_status_lock');
            $table->dropIndex('idx_words_gpt_enriched_at');
        });

        Schema::table('words', function (Blueprint $table) {
            $table->dropColumn(['gpt_status', 'gpt_enriched_at', 'gpt_attempts', 'gpt_last_error', 'gpt_lock_until', 'gpt_model']);
        });
        Schema::table('word_sentences', function (Blueprint $table) {
            $table->dropColumn(['gpt_status', 'gpt_enriched_at', 'gpt_attempts', 'gpt_last_error', 'gpt_lock_until', 'gpt_model']);
        });
    }
};
