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
        if (! Schema::hasTable('ppg') || Schema::hasColumn('ppg', 'is_serdik')) {
            return;
        }

        Schema::table('ppg', function (Blueprint $table) {
            $table->boolean('is_serdik')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('ppg') || ! Schema::hasColumn('ppg', 'is_serdik')) {
            return;
        }

        Schema::table('ppg', function (Blueprint $table) {
            $table->dropColumn('is_serdik');
        });
    }
};
