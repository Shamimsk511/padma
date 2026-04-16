<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_returns', function (Blueprint $table) {
            $table->decimal('deduction_percent', 5, 2)->default(0)->after('total');
            $table->decimal('deduction_amount', 10, 2)->default(0)->after('deduction_percent');
        });
    }

    public function down(): void
    {
        Schema::table('product_returns', function (Blueprint $table) {
            $table->dropColumn(['deduction_percent', 'deduction_amount']);
        });
    }
};
