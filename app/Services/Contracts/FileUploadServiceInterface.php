<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;

interface FileUploadServiceInterface
{
    /**
     * Upload a tenant logo and return the full URL.
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  string  $tenantId  The tenant's ID
     * @return string The full URL to the uploaded file
     */
    public function uploadTenantLogo(UploadedFile $file, string $tenantId): string;

    /**
     * Delete a tenant's logo file.
     *
     * @param  string  $logoUrl  The logo URL to delete
     */
    public function deleteTenantLogo(string $logoUrl): bool;
}
