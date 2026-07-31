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
        if (! Schema::hasTable('ppg')) {
            return;
        }

        Schema::table('ppg', function (Blueprint $table) {
            $table->string('statusppg')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('ppg')) {
            return;
        }

        Schema::table('ppg', function (Blueprint $table) {
            $table->dropColumn('statusppg');
        });
    }
};
