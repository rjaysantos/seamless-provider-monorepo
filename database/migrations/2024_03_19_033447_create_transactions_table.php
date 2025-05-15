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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('wcUserId');
            $table->decimal('debitAmount', 16, 2)->nullable();
            $table->decimal('creditAmount', 16, 2)->nullable();
            $table->decimal('bonusAmount', 16, 2)->nullable();
            $table->integer('wcPrdId');
            $table->string('wcTxnId', 100);
            $table->string('wcDebitRoundId', 100)->nullable();
            $table->string('wcCreditRoundId', 100)->nullable();
            $table->integer('wcGameId');
            $table->string('wcTableId', 50)->nullable();
            $table->integer('wcBonusType')->nullable();
            $table->integer('wcCreditType')->nullable();
            $table->dateTime('wcDebitTime')->nullable();
            $table->dateTime('wcCreditTime')->nullable();
            $table->dateTime('wcBonusTime')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
