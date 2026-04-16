<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentHistory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'product_id',
        'company_id',
        'category_id',
        'godown_id',
        'adjusted_by',
        'system_stock',
        'physical_count',
        'difference',
        'adjusted_at',
    ];

    protected $casts = [
        'system_stock' => 'decimal:2',
        'physical_count' => 'decimal:2',
        'difference' => 'decimal:2',
        'adjusted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
