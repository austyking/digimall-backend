<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use JsonException;

final readonly class AdminUpdateTenantDTO
{
    public function __construct(
        public ?string $displayName = null,
        public ?string $subdomain = null,
        public ?string $description = null,
        public ?bool $active = null,
        public ?array $settings = null,
        public ?UploadedFile $logo = null,
        public ?string $logoUrl = null,
        public ?string $updatedBy = null,
    ) {}

    /**
     * Create DTO from request.
     */
    public static function fromRequest(Request $request): self
    {
        // Handle settings (might be JSON string from FormData)
        $settings = null;
        if ($request->has('settings')) {
            $settingsInput = $request->input('settings');

            // If settings is a JSON string (from FormData), decode it
            if (is_string($settingsInput)) {
                try {
                    $settings = json_decode($settingsInput, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new \InvalidArgumentException(
                        'Invalid JSON format for settings: '.$e->getMessage(),
                        0,
                        $e
                    );
                }
            } else {
                $settings = $settingsInput;
            }
        }

        return new self(
            displayName: $request->input('display_name'),
            subdomain: $request->input('subdomain'),
            description: $request->input('description'),
            active: $request->has('active') ? (bool) $request->input('active') : null,
            settings: $settings,
            logo: $request->hasFile('logo') ? $request->file('logo') : null,
            logoUrl: $request->input('logo_url'),
            updatedBy: $request->user()?->id,
        );
    }

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            displayName: $data['display_name'] ?? null,
            subdomain: $data['subdomain'] ?? null,
            description: $data['description'] ?? null,
            active: isset($data['active']) ? (bool) $data['active'] : null,
            settings: $data['settings'] ?? null,
            logoUrl: $data['logo_url'] ?? null,
            updatedBy: $data['updated_by'] ?? null,
        );
    }

    /**
     * Convert DTO to array, excluding null values.
     */
    public function toArray(): array
    {
        return array_filter([
            'display_name' => $this->displayName,
            'subdomain' => $this->subdomain,
            'description' => $this->description,
            'active' => $this->active,
            'logo_url' => $this->logoUrl,
            'settings' => $this->settings,
            'updated_by' => $this->updatedBy,
        ], fn ($value) => $value !== null);
    }
}
