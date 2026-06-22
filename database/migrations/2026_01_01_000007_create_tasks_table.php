<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Bezug: meist eine Site, optional ein beliebiges Subjekt (License, Obligation …)
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->nullableMorphs('subject');                // subject_type / subject_id

            $table->string('title');
            $table->text('description')->nullable();

            // Quelle / Typ der Aufgabe – treibt Icons & Filter im Cockpit
            $table->string('type')->default('manual');        // update|ssl_expiry|domain_expiry|license_expiry|php_eol|compliance|form_check|manual
            $table->string('severity')->default('info');      // info|warning|critical
            $table->string('status')->default('open');        // open|in_progress|blocked|done|dismissed

            $table->date('due_date')->nullable();             // Frist (für Kalender/Fristenliste)
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('resolved_at')->nullable();
            $table->boolean('auto_generated')->default(false); // vom System erzeugt (idempotent über dedupe_key)
            $table->string('dedupe_key')->nullable();          // verhindert Doppel-Tasks bei wiederholten Scans

            $table->timestamps();

            $table->unique('dedupe_key');
            $table->index(['status', 'severity']);
            $table->index(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
