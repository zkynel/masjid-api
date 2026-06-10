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
        Schema::table('posts', function (Blueprint $table) {
            $table->enum('type', ['berita', 'pengumuman', 'kegiatan', 'halaman'])
                ->default('berita')
                ->after('mosque_id');

            $table->index(['mosque_id', 'type', 'status']);
            $table->unique(['mosque_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['mosque_id', 'slug']);
            $table->dropIndex(['mosque_id', 'type', 'status']);
            $table->dropColumn('type');
        });
    }
};
