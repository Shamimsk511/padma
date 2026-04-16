<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('godown_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('system_stock', 12, 2);
            $table->decimal('physical_count', 12, 2);
            $table->decimal('difference', 12, 2);
            $table->timestamp('adjusted_at')->useCurrent();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'adjusted_at'], 'idx_stock_adj_hist_tenant_date');
            $table->index(['tenant_id', 'product_id'], 'idx_stock_adj_hist_tenant_product');
            $table->index(['tenant_id', 'godown_id'], 'idx_stock_adj_hist_tenant_godown');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_histories');
    }
};
