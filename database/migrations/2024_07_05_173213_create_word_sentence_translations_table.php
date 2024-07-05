<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWordSentenceTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('word_sentence_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('word_sentence_id');
            $table->enum('language', ['ru', 'uz', 'az']);
            $table->text('translation');
            $table->timestamps();

            $table->foreign('word_sentence_id')->references('id')->on('word_sentences')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('word_sentence_translations');
    }
}
