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
        Schema::create('post_detail', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('post_id');
            $table->foreign('post_id')
                ->references('id')
                ->on('sosial_post')
                ->onDelete('cascade');
            $table->text('caption');
            $table->text('hashtags')->nullable();
            $table->string('text_template', 60)->nullable();
            $table->enum('media_type', ['image','video']);
            $table->string('file_path', 500);
            $table->string('file_url', 500)->nullable();
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('checksum', 64)->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_detail');
    }
};
