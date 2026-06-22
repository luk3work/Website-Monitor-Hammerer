<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Selbstheilend: eine evtl. aus einem fehlgeschlagenen Lauf halb angelegte
        // (leere) Tabelle wird verworfen, damit die Migration sauber durchläuft.
        Schema::dropIfExists('audit_log');

        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');                         // created|updated|deleted|approved|secret_rotated …
            // nullableMorphs() legt die Spalten auditable_type/auditable_id UND
            // bereits den passenden Kombi-Index an – daher KEIN zusätzlicher index().
            $table->nullableMorphs('auditable');
            $table->json('changes')->nullable();              // {before:{…}, after:{…}}
            $table->string('ip')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
