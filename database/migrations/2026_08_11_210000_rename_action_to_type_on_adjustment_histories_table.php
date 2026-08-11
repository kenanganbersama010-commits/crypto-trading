<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE adjustment_histories RENAME COLUMN action TO type');
        } else {
            DB::statement('ALTER TABLE adjustment_histories CHANGE action type VARCHAR(255) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE adjustment_histories RENAME COLUMN type TO action');
        } else {
            DB::statement('ALTER TABLE adjustment_histories CHANGE type action VARCHAR(255) NOT NULL');
        }
    }
};
