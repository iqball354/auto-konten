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
        Schema::table('sosial_post', function (Blueprint $table) {
            $table->index(['user_id', 'deleted_at', 'created_at'], 'idx_sp_user_deleted_created');
            $table->index(['user_id', 'status', 'created_at'], 'idx_sp_user_status_created');
        });

        Schema::table('post_logs', function (Blueprint $table) {
            $table->index(['post_id', 'status', 'executed_at'], 'idx_pl_post_status_executed');
            $table->index(['status', 'executed_at'], 'idx_pl_status_executed');
        });

        Schema::table('post_scheduler', function (Blueprint $table) {
            $table->index(['status', 'scheduled_at'], 'idx_ps_status_scheduled');
            $table->index(['detail_id'], 'idx_ps_detail_id');
        });

        Schema::table('post_detail', function (Blueprint $table) {
            $table->index(['post_id'], 'idx_pd_post_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read', 'created_at'], 'idx_nf_user_read_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_nf_user_read_created');
        });

        Schema::table('post_detail', function (Blueprint $table) {
            $table->dropIndex('idx_pd_post_id');
        });

        Schema::table('post_scheduler', function (Blueprint $table) {
            $table->dropIndex('idx_ps_detail_id');
            $table->dropIndex('idx_ps_status_scheduled');
        });

        Schema::table('post_logs', function (Blueprint $table) {
            $table->dropIndex('idx_pl_status_executed');
            $table->dropIndex('idx_pl_post_status_executed');
        });

        Schema::table('sosial_post', function (Blueprint $table) {
            $table->dropIndex('idx_sp_user_status_created');
            $table->dropIndex('idx_sp_user_deleted_created');
        });
    }
};
