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
        Schema::create('attendance_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances');
            $table->foreignId('attendance_history_id')->nullable()->constrained('attendance_histories');
            $table->foreignId('applied_by')->nullable()->constrained('users')->default(null);
            $table->dateTime('applied_at')->nullable()->default(null);
            $table->foreignId('approved_by')->nullable()->constrained('users')->default(null);
            $table->dateTime('approved_at')->nullable()->default(null);;
            $table->string('note', 255)->nullable();
            $table->timestamps();
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
