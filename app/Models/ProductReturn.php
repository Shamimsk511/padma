<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class ProductReturn extends Model
{
    use HasFactory, BelongsToTenant;
    
    protected $table = 'product_returns'; // Explicit table name for clarity

    protected $fillable = [
        'return_number',
        'customer_id',
        'invoice_id',
        'return_date',
        'subtotal',
        'tax',
        'total',
        'deduction_percent',
        'deduction_amount',
        'payment_method',
        'status',
        'notes',
    ];

    public function getNetTotalAttribute(): float
    {
        return (float) $this->total - (float) $this->deduction_amount;
    }

    protected $casts = [
        'return_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(ProductReturnItem::class, 'return_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'return_id');
    }

    // Generate next return number
    public static function getNextReturnNumber()
    {
        $lastReturn = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastReturn ? (int)substr($lastReturn->return_number, 3) + 1 : 1000;
        return 'RET' . $nextNumber;
    }
}
