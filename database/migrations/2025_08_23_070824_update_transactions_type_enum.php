<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit', 'withdrawal', 'transfer', 'payment', 'transfer_out', 'transfer_in')");
        }
        
        // For PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT transactions_type_check");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type::text = ANY (ARRAY['deposit', 'withdrawal', 'transfer', 'payment', 'transfer_out', 'transfer_in']::text[]))");
        }
    }

    public function down(): void
    {
        // Revert changes if needed
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit', 'withdrawal', 'transfer', 'payment')");
        }
        
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT transactions_type_check");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type::text = ANY (ARRAY['deposit', 'withdrawal', 'transfer', 'payment']::text[]))");
        }
    }
};