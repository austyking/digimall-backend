<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdminTenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'domain' => $this->domains->first()?->domain ?? null,
            'logo_url' => $this->logo_url,
            'status' => $this->status,
            'settings' => $this->settings,
            'domains' => $this->domains->map(fn ($domain) => [
                'id' => $domain->id,
                'domain' => $domain->domain,
            ]),
            'status_history' => $this->settings['status_history'] ?? [],
            'theme' => [
                'primary_color' => $this->getSetting('theme.primary_color', '#1976d2'),
                'secondary_color' => $this->getSetting('theme.secondary_color', '#dc004e'),
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
