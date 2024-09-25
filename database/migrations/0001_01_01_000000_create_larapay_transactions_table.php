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
        Schema::create('larapay_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('gateway')->nullable();
            $table->enum('mode', ['sandbox', 'live']);
            $table->string('type')->nullable();
            $table->string('uid')->nullable()->uniqid();
            $table->string('refrance')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency')->nullable();
            $table->json('response')->nullable();
            $table->string('status')->nullable();
            $table->nullableMorphs('transactionable', 'lara_trans_id');
            $table->foreignId('parent_id')->nullable()->constrained('larapay_transactions');
            $table->unique(['gateway', 'refrance']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('larapaytransactions');
    }
};
