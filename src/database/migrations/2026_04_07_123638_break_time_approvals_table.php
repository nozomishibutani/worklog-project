<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('break_time_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_approval_id')->constrained('attendance_approvals');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
