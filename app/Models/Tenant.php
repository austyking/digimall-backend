<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasDomains;
    use HasFactory;
    use SoftDeletes;

    /**
     * Get the columns that should NOT be stored in the data column.
     * These are real database columns.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'display_name',
            'description',
            'logo_url',
            'status',
            'settings',
        ];
    }

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be guarded.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'data' => 'array',
    ];

    /**
     * Get the tenant's settings.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a tenant setting.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Get the tenant's branding configuration.
     */
    public function getBrandingConfig(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->display_name,
            'logo_url' => $this->logo_url,
            'primary_color' => $this->getSetting('theme.primary_color', '#1976d2'),
            'secondary_color' => $this->getSetting('theme.secondary_color', '#dc004e'),
        ];
    }

    /**
     * Check if the tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the tenant is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Activate the tenant.
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the tenant.
     */
    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }
}
