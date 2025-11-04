<?php

namespace App\Models;

use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasDomains;

    /**
     * Get the columns that should NOT be stored in the data column.
     * These are real database columns.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'subdomain',
            'display_name',
            'logo_url',
            'active',
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
        'active' => 'boolean',
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
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'subdomain' => $this->subdomain,
        ];
    }
}
