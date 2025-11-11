<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use JsonException;

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

        // Parse settings from request
        $settings = [];
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
                // Already an array (from standard JSON request)
                $settings = $settingsInput;
            }
        }

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
