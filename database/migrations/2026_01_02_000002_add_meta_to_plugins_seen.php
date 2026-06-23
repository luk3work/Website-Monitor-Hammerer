<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plugins_seen', function (Blueprint $table) {
            if (! Schema::hasColumn('plugins_seen', 'author')) {
                $table->string('author')->nullable()->after('name');
            }
            if (! Schema::hasColumn('plugins_seen', 'plugin_uri')) {
                $table->string('plugin_uri')->nullable()->after('author');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plugins_seen', function (Blueprint $table) {
            $table->dropColumn(['author', 'plugin_uri']);
        });
    }
};
