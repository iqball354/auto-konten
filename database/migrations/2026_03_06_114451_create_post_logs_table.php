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
        Schema::create('post_logs', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('post_id');
            $table->foreign('post_id')
                ->references('id')
                ->on('sosial_post')
                ->onDelete('cascade');
            $table->enum('status', [
                'success',
                'failed'
            ]);
            $table->string('platform_post_id')->nullable(); 
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_payload')->nullable(); 
            $table->timestamp('executed_at');
            $table->timestamp('created_at')->useCurrent();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_logs');
    }
};
