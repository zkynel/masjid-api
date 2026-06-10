<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            $table->string('waqf_imb_document_path')->nullable()->after('logo_path');
            $table->string('management_decree_document_path')->nullable()->after('waqf_imb_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            $table->dropColumn([
                'waqf_imb_document_path',
                'management_decree_document_path',
            ]);
        });
    }
};