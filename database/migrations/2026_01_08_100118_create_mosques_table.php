<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mosques', function (Blueprint $table) {
            $table->id();

            // Identitas tenant
            $table->string('name')->default('Masjid Default');
            $table->string('slug')->unique(); // ini = subdomain/tenant key (mis: masjid-alghifari)

            // Opsional untuk future: custom domain
            $table->string('custom_domain')->nullable()->unique();

            // Profil masjid
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('contact')->nullable();
            $table->string('logo_path')->nullable();

            // Template
            $table->string('template_code')->nullable(); // mis: "TEMPLATE_A"

            // Onboarding & Verifikasi
            $table->enum('verification_status', ['draft', 'submitted', 'verified', 'rejected'])->default('draft');
            $table->timestamp('verification_submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable(); // catatan jika ditolak/di-approve

            // Terms
            $table->timestamp('terms_accepted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mosques');
    }
};
