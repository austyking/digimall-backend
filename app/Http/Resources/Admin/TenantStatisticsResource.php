<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_count' => $this->resource['total_count'] ?? 0,
            'active_count' => $this->resource['active_count'] ?? 0,
            'inactive_count' => $this->resource['inactive_count'] ?? 0,
            'activation_rate' => $this->resource['activation_rate'] ?? 0.0,
            'growth' => [
                'last_7_days' => $this->resource['growth']['last_7_days'] ?? 0,
                'last_30_days' => $this->resource['growth']['last_30_days'] ?? 0,
                'last_90_days' => $this->resource['growth']['last_90_days'] ?? 0,
            ],
        ];
    }
}
