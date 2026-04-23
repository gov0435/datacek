<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            DELETE FROM ppg
            WHERE ctid NOT IN (
                SELECT MIN(ctid) FROM ppg GROUP BY ptk_id
            )
        ');

        Schema::table('ppg', function (Blueprint $table) {
            $table->dropIndex('ppg_ptk_id_index');
            $table->unique('ptk_id');
        });
    }

    public function down(): void
    {
        Schema::table('ppg', function (Blueprint $table) {
            $table->dropUnique(['ptk_id']);
            $table->index('ptk_id');
        });
    }
};
