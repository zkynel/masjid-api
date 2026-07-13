<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Kajian fields
            $table->date('event_date')->nullable()->after('cover_image_path');
            $table->time('event_time')->nullable()->after('event_date');
            $table->string('speaker')->nullable()->after('event_time');
            $table->string('location')->nullable()->after('speaker');

            // Artikel fields
            $table->string('author')->nullable()->after('location');
            $table->string('category')->nullable()->after('author');
            $table->date('article_date')->nullable()->after('category');
            $table->text('excerpt')->nullable()->after('article_date');

            // Program fields
            $table->string('target_url')->nullable()->after('excerpt');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'event_date',
                'event_time',
                'speaker',
                'location',
                'author',
                'category',
                'article_date',
                'excerpt',
                'target_url',
            ]);
        });
    }
};
