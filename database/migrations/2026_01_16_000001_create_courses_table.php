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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tagline');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('thumbnail', 500)->nullable();
            $table->string('instructor_name', 100);
            $table->enum('type', ['lecture', 'mini', 'full']);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('portaly_url', 500)->nullable();
            $table->string('portaly_product_id', 100)->nullable();
            $table->timestamps();

            $table->index('is_published');
            $table->index('sort_order');
            $table->index('portaly_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
