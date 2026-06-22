<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            // Kerndaten aus dem Bericht (denormalisiert für schnelle Auswertung)
            $table->string('wp_version')->nullable();
            $table->string('wp_update')->nullable();          // verfügbare Core-Version oder null
            $table->string('php_version')->nullable();
            $table->string('mysql_version')->nullable();
            $table->boolean('https')->default(true);
            $table->boolean('is_multisite')->default(false);

            $table->unsignedSmallInteger('plugins_total')->default(0);
            $table->unsignedSmallInteger('plugins_active')->default(0);
            $table->unsignedSmallInteger('plugins_update')->default(0);
            $table->unsignedSmallInteger('themes_update')->default(0);

            // Fingerprint-Signale als JSON (Auswertung/Compliance-Matching serverseitig)
            $table->json('fingerprint')->nullable();

            // Vollständiger Rohbericht (Nachvollziehbarkeit / spätere Re-Auswertung)
            $table->json('raw')->nullable();

            $table->string('reporter_version')->nullable();
            $table->timestamp('collected_at')->nullable();    // Zeitpunkt laut Reporter
            $table->timestamp('received_at');                 // Eingang im Cockpit

            $table->timestamps();

            $table->index(['site_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_snapshots');
    }
};
