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
        Schema::create('word_sentences', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('words_id')->nullable(false);
            $table->text('content')->nullable(false);
            $table->text('content_translate')->nullable();

            $table
                ->foreign('words_id','FK_word_sentence_word')
                ->references('id')
                ->on('words')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_sentences');
    }
};
