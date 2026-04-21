<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ppg')) {
            return;
        }

        Schema::table('ppg', function (Blueprint $table) {
            $table->index('ptk_id');
            $table->index('jenjang');
            $table->index('kota');
            $table->index('nama');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ppg')) {
            return;
        }

        Schema::table('ppg', function (Blueprint $table) {
            $table->dropIndex(['ptk_id']);
            $table->dropIndex(['jenjang']);
            $table->dropIndex(['kota']);
            $table->dropIndex(['nama']);
        });
    }
};
