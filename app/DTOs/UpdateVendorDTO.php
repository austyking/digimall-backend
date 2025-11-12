<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for vendor profile updates.
 *
 * Encapsulates optional update data for existing vendor accounts.
 * All fields are optional to support partial updates.
 */
final readonly class UpdateVendorDTO
{
    public function __construct(
        // Optional business information
        public ?string $businessName = null,
        public ?string $contactName = null,
        public ?string $phone = null,
        public ?string $description = null,
        public ?string $logoUrl = null,

        // Optional address information
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $postalCode = null,
        public ?string $country = null,

        // Optional business details
        public ?string $businessRegistrationNumber = null,
        public ?string $taxId = null,
        public ?string $bankName = null,
        public ?string $bankAccountNumber = null,
        public ?string $bankAccountName = null,

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
            businessName: $data['business_name'] ?? null,
            contactName: $data['contact_name'] ?? null,
            phone: $data['phone'] ?? null,
            description: $data['description'] ?? null,
            logoUrl: $data['logo_url'] ?? null,
            addressLine1: $data['address_line_1'] ?? null,
            addressLine2: $data['address_line_2'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            country: $data['country'] ?? null,
            businessRegistrationNumber: $data['business_registration_number'] ?? null,
            taxId: $data['tax_id'] ?? null,
            bankName: $data['bank_name'] ?? null,
            bankAccountNumber: $data['bank_account_number'] ?? null,
            bankAccountName: $data['bank_account_name'] ?? null,
            settings: $data['settings'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Convert DTO to array, excluding null values.
     * Only non-null fields will be updated.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->businessName !== null) {
            $data['business_name'] = $this->businessName;
        }

        if ($this->contactName !== null) {
            $data['contact_name'] = $this->contactName;
        }

        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->logoUrl !== null) {
            $data['logo_url'] = $this->logoUrl;
        }

        if ($this->addressLine1 !== null) {
            $data['address_line_1'] = $this->addressLine1;
        }

        if ($this->addressLine2 !== null) {
            $data['address_line_2'] = $this->addressLine2;
        }

        if ($this->city !== null) {
            $data['city'] = $this->city;
        }

        if ($this->state !== null) {
            $data['state'] = $this->state;
        }

        if ($this->postalCode !== null) {
            $data['postal_code'] = $this->postalCode;
        }

        if ($this->country !== null) {
            $data['country'] = $this->country;
        }

        if ($this->businessRegistrationNumber !== null) {
            $data['business_registration_number'] = $this->businessRegistrationNumber;
        }

        if ($this->taxId !== null) {
            $data['tax_id'] = $this->taxId;
        }

        if ($this->bankName !== null) {
            $data['bank_name'] = $this->bankName;
        }

        if ($this->bankAccountNumber !== null) {
            $data['bank_account_number'] = $this->bankAccountNumber;
        }

        if ($this->bankAccountName !== null) {
            $data['bank_account_name'] = $this->bankAccountName;
        }

        if ($this->settings !== null) {
            $data['settings'] = $this->settings;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
