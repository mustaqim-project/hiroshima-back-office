<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-BR-01, FR-BR-02, FR-BR-03, FR-INV-00, FR-INV-12, FR-INV-13, FSD-02, FSD-05, FSD-07, FSD-12
 */
class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'phone',
        'address',
        'tax_percent',
        'service_charge_percent',
        'tax_included',
        'rounding_mode',
        'pin_void_hash',
        'pin_discount_hash',
        'discount_percent_threshold',
        'discount_amount_threshold',
        'inventory_enabled',
        'negative_stock_policy',
        'auto_sold_out',
        'print_kitchen_copy',
        'printer_paper_width',
        'is_active',
    ];

    protected $hidden = [
        'pin_void_hash',
        'pin_discount_hash',
    ];

    protected function casts(): array
    {
        return [
            'tax_percent' => 'decimal:2',
            'service_charge_percent' => 'decimal:2',
            'tax_included' => 'boolean',
            'discount_percent_threshold' => 'decimal:2',
            'discount_amount_threshold' => 'integer',
            'inventory_enabled' => 'boolean',
            'auto_sold_out' => 'boolean',
            'print_kitchen_copy' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function inventoryPeriods()
    {
        return $this->hasMany(BranchInventoryPeriod::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(BranchPaymentMethod::class);
    }
}
