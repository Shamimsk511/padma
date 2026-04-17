<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('business_settings', 'delivery_alert_enabled')) {
                $table->boolean('delivery_alert_enabled')->default(true)->after('customer_qr_expiry_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            if (Schema::hasColumn('business_settings', 'delivery_alert_enabled')) {
                $table->dropColumn('delivery_alert_enabled');
            }
        });
    }
};
