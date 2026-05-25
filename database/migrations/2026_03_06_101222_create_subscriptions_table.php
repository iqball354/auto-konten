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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedinteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->enum('plan', ['basic', 'pro', 'premium'])->default('basic');  
            $table->enum('status', ['active', 'expired', 'canceled'])->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expired_at')->nullable();
            $table->integer('max_accounts')->default(1);
            $table->integer('max_posts_per_day')->default(25);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};