<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for vendor registration.
 *
 * Encapsulates all required and optional data for creating a new vendor account.
 * Following the DTO pattern for type-safe data passing between layers.
 */
final readonly class RegisterVendorDTO
{
    public function __construct(
        // Required fields
        public string $tenantId,
        public string $userId,
        public string $businessName,
        public string $contactName,
        public string $email,

        // Optional contact information
        public ?string $phone = null,
        public ?string $description = null,
        public ?string $logoUrl = null,

        // Optional address information
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $postalCode = null,
        public string $country = 'Ghana',

        // Optional business details
        public ?string $businessRegistrationNumber = null,
        public ?string $taxId = null,
        public ?string $bankName = null,
        public ?string $bankAccountNumber = null,
        public ?string $bankAccountName = null,

        // Optional commission settings (defaults will be applied if not provided)
        public ?float $commissionRate = null,
        public ?string $commissionType = null,

        // Optional metadata
        public ?array $settings = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Create DTO from HTTP request data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            businessName: $data['business_name'],
            contactName: $data['contact_name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            description: $data['description'] ?? null,
            logoUrl: $data['logo_url'] ?? null,
            addressLine1: $data['address_line_1'] ?? null,
            addressLine2: $data['address_line_2'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            country: $data['country'] ?? 'Ghana',
            businessRegistrationNumber: $data['business_registration_number'] ?? null,
            taxId: $data['tax_id'] ?? null,
            bankName: $data['bank_name'] ?? null,
            bankAccountNumber: $data['bank_account_number'] ?? null,
            bankAccountName: $data['bank_account_name'] ?? null,
            commissionRate: isset($data['commission_rate']) ? (float) $data['commission_rate'] : null,
            commissionType: $data['commission_type'] ?? null,
            settings: $data['settings'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Convert DTO to array for repository/model operations.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'business_name' => $this->businessName,
            'contact_name' => $this->contactName,
            'email' => $this->email,
            'phone' => $this->phone,
            'description' => $this->description,
            'logo_url' => $this->logoUrl,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'business_registration_number' => $this->businessRegistrationNumber,
            'tax_id' => $this->taxId,
            'bank_name' => $this->bankName,
            'bank_account_number' => $this->bankAccountNumber,
            'bank_account_name' => $this->bankAccountName,
            'status' => 'pending', // Default status for new registrations
            'settings' => $this->settings,
            'metadata' => $this->metadata,
        ];

        // Add commission settings if provided, otherwise use system defaults
        if ($this->commissionRate !== null) {
            $data['commission_rate'] = $this->commissionRate;
        }

        if ($this->commissionType !== null) {
            $data['commission_type'] = $this->commissionType;
        }

        return $data;
    }

    /**
     * Validate that required fields are not empty.
     */
    public function validate(): bool
    {
        return ! empty($this->tenantId)
            && ! empty($this->userId)
            && ! empty($this->businessName)
            && ! empty($this->contactName)
            && ! empty($this->email)
            && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
