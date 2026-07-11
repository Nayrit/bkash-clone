<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('agent_commission', 15, 2)->default(0.00)->after('fee');
            $table->decimal('admin_fee', 15, 2)->default(0.00)->after('agent_commission');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['agent_commission', 'admin_fee']);
        });
    }
};
