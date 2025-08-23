<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_transfer_recipients', function (Blueprint $table) {
            $table->text('failure_reason')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bulk_transfer_recipients', function (Blueprint $table) {
            $table->string('failure_reason')->nullable()->change();
        });
    }
};