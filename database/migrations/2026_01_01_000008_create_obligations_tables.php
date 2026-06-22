<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Katalog der Pflichten (DSGVO, EAA/Barrierefreiheit, Cookie-Consent, Impressum, lokale Fonts …)
        Schema::create('obligations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                  // z. B. "dsgvo.consent", "eaa.accessibility"
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();           // "DSGVO", "Barrierefreiheit", "Impressum" …

            // Auto-Matching: bei welchem Fingerprint-Signal wird die Pflicht relevant?
            // z. B. {"has_tracking": true} -> Consent-Pflicht; {"has_forms": true} -> AV/Datenschutz
            $table->json('applies_when')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Zuordnung Pflicht <-> Site inkl. Soll-Ist
        Schema::create('site_obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('obligation_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default('open');        // open|met|not_applicable|in_review
            $table->boolean('auto_matched')->default(false);  // durch Fingerprint zugeordnet
            $table->date('due_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('last_checked_at')->nullable();

            $table->timestamps();

            $table->unique(['site_id', 'obligation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_obligations');
        Schema::dropIfExists('obligations');
    }
};
