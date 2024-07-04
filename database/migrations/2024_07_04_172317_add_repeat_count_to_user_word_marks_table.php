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
        Schema::table('user_word_marks', function (Blueprint $table) {
            $table->unsignedInteger('repeat_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_word_marks', function (Blueprint $table) {
            $table->dropColumn('repeat_count');
        });
    }
};
