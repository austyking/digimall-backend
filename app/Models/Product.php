<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Lunar\Models\Product as LunarProduct;

/**
 * Extended Product model for DigiMall.
 *
 * Extends Lunar's Product model to add vendor relationships
 * and multi-tenant functionality.
 */
class Product extends LunarProduct
{
    /**
     * The factory associated with the model.
     */
    protected static string $factory = \Database\Factories\ProductFactory::class;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'attribute_data',
        'product_type_id',
        'status',
        'brand_id',
        'vendor_id',
    ];

    /**
     * Get the vendor that owns the product.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Get the tenant through the vendor.
     */
    public function tenant(): HasOneThrough
    {
        return $this->hasOneThrough(
            Tenant::class,
            Vendor::class,
            'id', // Foreign key on vendors table
            'id', // Foreign key on tenants table
            'vendor_id', // Local key on products table
            'tenant_id' // Local key on vendors table
        );
    }

    /**
     * Scope products to a specific vendor.
     */
    public function scopeForVendor($query, string $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope products to a specific tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->whereHas('vendor', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });
    }
}
