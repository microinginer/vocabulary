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
        Schema::table('words', function (Blueprint $table) {
            $table->unsignedInteger('length')->after('translate');
            $table->string('pronunciation', 512)->nullable()->after('length');
            $table->tinyInteger('difficulty_level')->unsigned()->nullable()->after('pronunciation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropColumn('length');
            $table->dropColumn('pronunciation');
            $table->dropColumn('difficulty_level');
        });
    }
};
