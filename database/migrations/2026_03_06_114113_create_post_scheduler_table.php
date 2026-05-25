
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
        Schema::create('post_scheduler', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('detail_id');
            $table->foreign('detail_id')
                ->references('id')
                ->on('post_detail')
                ->onDelete('cascade');
            $table->unsignedInteger('sosial_account_id');
            $table->foreign('sosial_account_id')
                ->references('id')
                ->on('sosial_accounts')
                ->onDelete('cascade');
            $table->timestamp('scheduled_at');
            $table->timestamp('executed_at')->nullable();
            $table->enum('status', [
                'pending',
                'processing',
                'done',
                'failed'
            ])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_scheduler');
    }
};
