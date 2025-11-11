<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\FileUploadServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class FileUploadService implements FileUploadServiceInterface
{
    /**
     * Upload a tenant logo and return the full URL.
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  string  $tenantId  The tenant's ID
     * @return string The full URL to the uploaded file
     */
    public function uploadTenantLogo(UploadedFile $file, string $tenantId): string
    {
        // Store in tenant-specific directory
        $path = $file->store("tenants/{$tenantId}", 'public');

        // Return full URL
        return Storage::disk('public')->url($path);
    }

    /**
     * Delete a tenant's logo file.
     *
     * @param  string  $logoUrl  The logo URL to delete
     */
    public function deleteTenantLogo(string $logoUrl): bool
    {
        // Extract path from URL
        $path = str_replace(Storage::disk('public')->url(''), '', $logoUrl);

        return Storage::disk('public')->delete($path);
    }
}
