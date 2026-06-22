<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alert_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');                           // slack|teams|email|webhook
            $table->text('target')->nullable();               // Webhook-URL / E-Mail (Cast 'encrypted')
            $table->json('rules')->nullable();                // {min_severity:"warning", types:["update","ssl_expiry"]}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('alert_channel_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type');                           // offline|update|ssl_expiry|domain_expiry|license_expiry|form_check
            $table->string('severity')->default('warning');
            $table->string('message');
            $table->string('dedupe_key')->nullable();         // verhindert Alarm-Spam
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'severity']);
            $table->index('dedupe_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('alert_channels');
    }
};
