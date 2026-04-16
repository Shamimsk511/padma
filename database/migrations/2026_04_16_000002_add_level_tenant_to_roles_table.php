<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(0)->after('guard_name');
            $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['level', 'tenant_id']);
        });
    }
};
