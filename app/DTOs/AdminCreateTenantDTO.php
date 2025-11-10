<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class AdminCreateTenantDTO
{
    public function __construct(
        public string $name,
        public string $displayName,
        public ?string $description = null,
        public ?string $domain = null,
        public ?UploadedFile $logo = null,
        public bool $active = true,
        public array $settings = [],
        public ?string $createdBy = null,
    ) {}

    /**
     * Create DTO from HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        $user = $request->user();
        $userId = $user?->id;

        // Build nested settings structure from flat frontend format
        $settings = [];

        // Theme settings
        if ($request->has('theme_primary_color') || $request->has('theme_secondary_color')) {
            $settings['theme'] = array_filter([
                'primary_color' => $request->input('theme_primary_color'),
                'secondary_color' => $request->input('theme_secondary_color'),
            ], fn ($value) => $value !== null);
        }

        // Feature settings
        if ($request->has('hire_purchase_enabled') || $request->has('vendor_registration_enabled') || $request->has('multi_currency_enabled')) {
            $settings['features'] = [
                'hire_purchase_enabled' => $request->boolean('hire_purchase_enabled', true),
                'vendor_registration_enabled' => $request->boolean('vendor_registration_enabled', true),
                'multi_currency_enabled' => $request->boolean('multi_currency_enabled', false),
            ];
        }

        // Contact settings
        if ($request->has('contact_email') || $request->has('contact_phone') || $request->has('contact_address')) {
            $settings['contact'] = array_filter([
                'email' => $request->input('contact_email'),
                'phone' => $request->input('contact_phone'),
                'address' => $request->input('contact_address'),
            ], fn ($value) => $value !== null);
        }

        // Merge with any additional settings from request
        $additionalSettings = $request->input('settings', []);
        $settings = array_merge($settings, $additionalSettings);

        return new self(
            name: $request->input('name'),
            displayName: $request->input('display_name'),
            description: $request->input('description'),
            domain: $request->input('domain'),
            logo: $request->hasFile('logo') ? $request->file('logo') : null,
            active: $request->boolean('active', true),
            settings: $settings,
            createdBy: $userId,
        );
    }

    /**
     * Convert DTO to array for model creation.
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'status' => $this->active ? 'active' : 'inactive',
            'settings' => $this->settings,
        ];

        // Add domain if provided
        if ($this->domain) {
            $data['domain'] = $this->domain;
        }

        // Add logo file if provided (will be handled separately in repository)
        if ($this->logo) {
            $data['logo'] = $this->logo;
        }

        // Add audit trail
        if ($this->createdBy) {
            $data['settings']['created_by'] = [
                'user_id' => $this->createdBy,
                'at' => now()->toISOString(),
            ];
        }

        return $data;
    }
}
