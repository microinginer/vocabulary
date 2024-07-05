<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWordTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('word_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('word_id');
            $table->enum('language', ['ru', 'uz', 'az']);
            $table->string('translation', 512);
            $table->timestamps();

            $table->foreign('word_id')->references('id')->on('words')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('word_translations');
    }
}
