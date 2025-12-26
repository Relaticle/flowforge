<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default('todo');

            // DECIMAL(20,10) for position - 10 integer digits + 10 decimal places
            // Supports ~33 bisections before precision loss, with 65535 gap
            $table->decimal('order_position', 20, 10)->nullable();

            // Unique constraint per column to detect position collisions
            // Combined with jitter, this enables retry logic for concurrent safety
            $table->unique(['status', 'order_position'], 'unique_position_per_column');

            $table->string('priority')->default('medium');
            $table->timestamps();
        });
    }
};
