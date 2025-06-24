<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLanguageToWordsTable extends Migration
{
    public function up()
    {
        Schema::table('words', function (Blueprint $table) {
            $table->enum('language', ['en', 'fr', 'de', 'it'])->default('en')->after('word');
        });
    }

    public function down()
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
}
