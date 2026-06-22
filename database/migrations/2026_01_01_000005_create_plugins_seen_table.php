<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugins_seen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            $table->string('slug');
            $table->string('name')->nullable();
            $table->string('version')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('update_available')->default(false);
            $table->string('update_version')->nullable();

            // Freigabe-Steuerung: manche Updates bewusst (noch) nicht ausrollen
            $table->boolean('hold')->default(false);          // "noch nicht updaten"
            $table->string('hold_reason')->nullable();

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
            $table->index(['update_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins_seen');
    }
};
