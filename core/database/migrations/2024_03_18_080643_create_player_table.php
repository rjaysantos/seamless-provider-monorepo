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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->integer('branchId');
            $table->string('playId');
            $table->integer('memberId')->nullable();
            $table->string('username');
            $table->string('currency', 5);
            $table->string('language', 5);
            $table->string('country', 5);
            $table->string('gameId');
            $table->string('host');
            $table->tinyInteger('device');
            $table->tinyInteger('isTrial');
            $table->decimal('balance', 16, 2)->nullable();
            $table->integer('wcUserId')->nullable();
            $table->tinyInteger('wcStatus')->nullable();
            $table->string('wcUserName', 50)->nullable();
            $table->string('wcSID', 100)->nullable();
            $table->string('token', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player');
    }
};
