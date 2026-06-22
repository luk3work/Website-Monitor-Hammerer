<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();

            // Identität / Stammdaten
            $table->string('site_id')->unique();              // fachlicher Schlüssel, vom Reporter mitgesendet (z. B. "ried-immobilien")
            $table->string('label');                          // Anzeigename
            $table->string('url');                            // Primär-URL
            $table->enum('cms_type', ['wordpress', 'extern'])->default('wordpress');

            // Hosting / Domain
            $table->boolean('hosted_by_us')->default(true);   // liegt auf unserem Mittwald?
            $table->boolean('domain_by_us')->default(true);   // Domain bei uns registriert?
            $table->string('hosting_note')->nullable();

            // Paket / SLA
            $table->string('package_tier')->nullable();       // z. B. "Care Basic", "Care Pro"
            $table->unsignedSmallInteger('update_interval_days')->nullable(); // aus Tier abgeleitet
            $table->unsignedSmallInteger('sla_hours')->nullable();            // Reaktionszeit

            // Sicherheit (Reporter-Auth): Shared Secret für HMAC, verschlüsselt at rest
            // (Eloquent-Cast 'encrypted'). Wird zur Signaturprüfung im Klartext benötigt,
            // liegt aber nur verschlüsselt in der DB.
            $table->text('secret')->nullable();
            $table->timestamp('secret_rotated_at')->nullable();

            // Ablaufdaten (für Fristen/Handlungsliste)
            $table->date('ssl_expires_at')->nullable();
            $table->date('domain_expires_at')->nullable();

            // Letzter Stand (denormalisiert aus jüngstem Snapshot, für schnelle Tabellen)
            $table->string('wp_version')->nullable();
            $table->string('php_version')->nullable();
            $table->unsignedSmallInteger('pending_updates')->default(0);
            $table->timestamp('last_seen_at')->nullable();    // Dead-Man's-Switch
            $table->string('status')->default('unknown');     // abgeleitet: online|maintenance|offline|unknown

            // Lifecycle
            $table->boolean('is_archived')->default(false);   // Offboarding
            $table->json('tags')->nullable();

            $table->timestamps();

            $table->index(['status']);
            $table->index(['last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
