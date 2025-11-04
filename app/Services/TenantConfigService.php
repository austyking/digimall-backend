<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;

class TenantConfigService
{
    /**
     * Get the current tenant instance.
     */
    public function getCurrentTenant(): \Illuminate\Database\Eloquent\Model|\Stancl\Tenancy\Contracts\Tenant|null
    {
        if (! tenancy()->initialized) {
            return null;
        }

        return tenancy()->tenant;
    }

    /**
     * Get tenant branding configuration.
     */
    public function getBrandingConfig(): array
    {
        $tenant = $this->getCurrentTenant();

        if (! $tenant) {
            return $this->getDefaultBrandingConfig();
        }

        return $tenant->getBrandingConfig();
    }

    /**
     * Get default branding configuration for central app.
     */
    public function getDefaultBrandingConfig(): array
    {
        return [
            'name' => 'DigiMall',
            'display_name' => 'DigiMall - Professional Associations Marketplace',
            'logo_url' => '/assets/images/logo.png',
            'primary_color' => '#1976d2',
            'secondary_color' => '#dc004e',
            'subdomain' => 'central',
        ];
    }

    /**
     * Get a specific tenant setting.
     */
    public function getSetting(string $key, $default = null)
    {
        $tenant = $this->getCurrentTenant();

        if (! $tenant) {
            return $default;
        }

        return $tenant->getSetting($key, $default);
    }

    /**
     * Set a tenant setting.
     */
    public function setSetting(string $key, $value): bool
    {
        $tenant = $this->getCurrentTenant();

        if (! $tenant) {
            return false;
        }

        $tenant->setSetting($key, $value);

        return $tenant->save();
    }

    /**
     * Get tenant-specific configuration for API responses.
     */
    public function getApiConfig(): array
    {
        $branding = $this->getBrandingConfig();

        return [
            'tenant' => [
                'name' => $branding['name'],
                'display_name' => $branding['display_name'],
                'logo_url' => $branding['logo_url'],
                'theme' => [
                    'primary_color' => $branding['primary_color'],
                    'secondary_color' => $branding['secondary_color'],
                ],
            ],
            'features' => [
                'hire_purchase' => $this->getSetting('features.hire_purchase', true),
                'vendor_registration' => $this->getSetting('features.vendor_registration', true),
                'member_verification' => $this->getSetting('features.member_verification', true),
            ],
            'payment_gateways' => $this->getSetting('payment_gateways', [
                'moolre' => ['enabled' => true],
                'stripe' => ['enabled' => false],
                'flutterwave' => ['enabled' => false],
            ]),
        ];
    }

    /**
     * Get payment gateway configuration.
     */
    public function getPaymentGateways(): array
    {
        return $this->getSetting('payment_gateways', [
            'moolre' => [
                'enabled' => true,
                'api_key' => null,
                'secret_key' => null,
                'webhook_url' => null,
            ],
            'stripe' => [
                'enabled' => false,
                'publishable_key' => null,
                'secret_key' => null,
                'webhook_secret' => null,
            ],
            'flutterwave' => [
                'enabled' => false,
                'public_key' => null,
                'secret_key' => null,
                'encryption_key' => null,
            ],
        ]);
    }

    /**
     * Get association-specific API endpoints.
     */
    public function getAssociationApiConfig(): array
    {
        return $this->getSetting('association_api', [
            'base_url' => null,
            'api_key' => null,
            'endpoints' => [
                'member_verification' => '/api/verify-member',
                'loan_status' => '/api/loan-status',
                'hire_purchase_schema' => '/api/hire-purchase/schema',
                'hire_purchase_submit' => '/api/hire-purchase/submit',
            ],
        ]);
    }

    /**
     * Get SMS configuration.
     */
    public function getSmsConfig(): array
    {
        return $this->getSetting('sms', [
            'provider' => 'arkesel',
            'api_key' => null,
            'sender_id' => 'DigiMall',
        ]);
    }

    /**
     * Check if a feature is enabled for the current tenant.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return $this->getSetting("features.{$feature}", false);
    }

    /**
     * Enable or disable a feature for the current tenant.
     */
    public function toggleFeature(string $feature, bool $enabled): bool
    {
        return $this->setSetting("features.{$feature}", $enabled);
    }
}
