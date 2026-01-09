<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mosque_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('role_in_mosque')->default('admin'); // admin/editor/viewer

            $table->timestamps();

            $table->unique(['mosque_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mosque_user');
    }
};
