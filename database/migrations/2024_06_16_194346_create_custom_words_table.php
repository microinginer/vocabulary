<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('custom_word_lists')->onDelete('cascade');
            $table->foreignId('word_id')->nullable()->constrained('words')->onDelete('set null');
            $table->string('custom_word')->nullable();
            $table->string('custom_translation')->nullable();
            $table->string('custom_pronunciation')->nullable();
            $table->text('custom_example_sentence')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_words');
    }
};
