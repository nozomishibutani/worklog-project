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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->date('work_date');
            $table->unique(['user_id', 'work_date']);
            $table->timestamp('clock_in');
            $table->timestamp('clock_out')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('corrected_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->string('note', 255)->nullable();
            $table->string('status', 10)->default('draft')
                ->comment('draft=登録漏れ, completed= 登録完了, pending=承認待ち, approved=承認済み');
            $table->timestamps();
            $table->index('status');
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
