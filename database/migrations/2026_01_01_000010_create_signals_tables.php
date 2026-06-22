<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signal_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // "endoflife.date", "WP.org", "Signal-Mailbox", "Newsletter X"
            $table->string('type')->default('feed');          // feed|mailbox|api|manual
            $table->string('endpoint')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_source_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('external_ref')->nullable();       // Message-ID / URL zur Dedupe
            $table->string('severity')->default('info');      // info|warning|critical

            // KI-Triage-Ergebnis (Daten ≠ Befehle, festes Schema, Mensch bestätigt)
            $table->string('triage_status')->default('new');  // new|triaged|approved|dismissed
            $table->json('triage')->nullable();               // {summary, affects:[fingerprint-bedingungen], suggested_obligation_keys:[]}
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['triage_status']);
            $table->unique('external_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
        Schema::dropIfExists('signal_sources');
    }
};
