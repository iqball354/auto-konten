<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('api_tokens', 'short_lived_token')) {
                $table->text('short_lived_token')->nullable()->after('meta_app_secret');
            }

            if (!Schema::hasColumn('api_tokens', 'oauth_state')) {
                $table->string('oauth_state', 100)->nullable()->after('long_lived_token');
            }

            if (!Schema::hasColumn('api_tokens', 'oauth_redirect_uri')) {
                $table->string('oauth_redirect_uri', 500)->nullable()->after('oauth_state');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('api_tokens', 'oauth_redirect_uri')) {
                $columnsToDrop[] = 'oauth_redirect_uri';
            }

            if (Schema::hasColumn('api_tokens', 'oauth_state')) {
                $columnsToDrop[] = 'oauth_state';
            }

            if (Schema::hasColumn('api_tokens', 'short_lived_token')) {
                $columnsToDrop[] = 'short_lived_token';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
