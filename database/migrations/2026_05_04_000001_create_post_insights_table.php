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
        Schema::create('post_insights', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('post_id');
            $table->foreign('post_id')
                ->references('id')
                ->on('sosial_post')
                ->onDelete('cascade'); // Meta post ID
             $table->unsignedInteger('sosial_account_id');
            $table->foreign('sosial_account_id')
                ->references('id')
                ->on('sosial_accounts')
                ->onDelete('cascade');
            // Metrics from Meta Graph API
            $table->integer('metric_impressions')->default(0);
            $table->integer('metric_reach')->default(0);
            $table->integer('metric_engaged_users')->default(0);
            $table->integer('metric_clicks')->default(0);
            
            // Temporal data
            $table->unsignedTinyInteger('hour')->nullable(); // 0-23
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0-6 (Sunday=0)
            
            // Timestamps
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for query optimization
            $table->index(['sosial_account_id', 'recorded_at']);
            $table->index(['hour', 'day_of_week']);
            $table->unique(['post_id', 'sosial_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_insights');
    }
};
