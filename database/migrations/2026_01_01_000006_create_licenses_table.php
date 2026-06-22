<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();

            $table->string('product');                        // z. B. "WPForms Pro", "Borlabs Cookie"
            $table->string('vendor')->nullable();
            $table->text('license_key')->nullable();          // Eloquent-Cast 'encrypted' (at rest verschlüsselt)
            $table->date('expires_at')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('billing_cycle')->nullable();      // "jährlich", "einmalig"
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
