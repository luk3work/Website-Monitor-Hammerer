<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Katalog der buchbaren Pakete inkl. Abhängigkeitslogik.
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // z. B. "security.advanced"
            $table->string('name');
            $table->string('category');               // hosting|service
            $table->string('group')->nullable();      // hosting_tier|addon|update|seo|performance|datenschutz|security|a11y|reporting
            $table->text('description')->nullable();

            $table->decimal('price_once', 10, 2)->nullable();
            $table->decimal('price_monthly', 10, 2)->nullable();
            $table->decimal('price_yearly', 10, 2)->nullable();

            $table->json('requires')->nullable();       // ALLE müssen gebucht sein
            $table->json('requires_any')->nullable();   // MINDESTENS EINES gebucht
            $table->json('excludes')->nullable();       // KEINES darf gebucht sein

            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // Zuordnung Paket <-> Site inkl. Zustand (gebucht/abgewählt).
        Schema::create('site_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('state')->default('booked'); // booked|declined
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_packages');
        Schema::dropIfExists('packages');
    }
};
