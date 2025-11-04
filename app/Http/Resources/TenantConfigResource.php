<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tenant' => [
                'id' => $this->id,
                'name' => $this->name,
                'display_name' => $this->display_name,
                'description' => $this->description,
                'active' => $this->active,
            ],
            'branding' => $this->getBrandingConfig(),
            'features' => $this->getSetting('features', []),
            'payment_gateways' => $this->getSetting('payment_gateways', []),
            'settings' => $this->settings ?? [],
        ];
    }
}
