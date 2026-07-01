<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('own');     // own | external
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('package_key')->nullable();   // welches gebuchte Paket (external)
            $table->string('homepage')->nullable();
            $table->string('repo_url')->nullable();      // Update-Quelle / Repo (own)
            $table->string('current_version')->nullable();
            $table->text('notes')->nullable();           // eigene Erklärungen/Hinweise
            $table->json('news')->nullable();            // später automatisch gesammelte News
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
