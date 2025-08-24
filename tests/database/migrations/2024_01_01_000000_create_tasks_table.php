<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default('todo');

            $this->definePositionColumn($table);

            $table->string('priority')->default('medium');
            $table->timestamps();
        });
    }

    private function definePositionColumn(Blueprint $table): void
    {
        $driver = DB::connection()->getDriverName();

        $positionColumn = $table->string('order_position', 64)->nullable();

        match ($driver) {
            'pgsql' => $positionColumn->collation('C'),
            'mysql' => $positionColumn->collation('utf8mb4_bin'),
            default => null,
        };
    }
};
