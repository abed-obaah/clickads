<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique();
            $table->decimal('total_amount', 15, 2);
            $table->integer('recipient_count');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'partial'])->default('pending');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
        });

        Schema::create('bulk_transfer_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_transfer_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('failure_reason')->nullable();
            $table->timestamps();
            
            $table->index(['bulk_transfer_id', 'status']);
            $table->index(['recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_transfer_recipients');
        Schema::dropIfExists('bulk_transfers');
    }
};