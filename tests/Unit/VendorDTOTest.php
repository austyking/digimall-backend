<?php

declare(strict_types=1);

use App\DTOs\RegisterVendorDTO;
use App\DTOs\UpdateVendorDTO;

describe('RegisterVendorDTO', function () {
    test('creates DTO with all required fields', function () {
        $dto = new RegisterVendorDTO(
            tenantId: null, // Will be resolved from current tenant
            businessName: 'Test Pharmacy',
            contactName: 'John Doe',
            email: 'john@testpharmacy.com'
        );

        expect($dto->businessName)->toBe('Test Pharmacy')
            ->and($dto->contactName)->toBe('John Doe')
            ->and($dto->email)->toBe('john@testpharmacy.com')
            ->and($dto->country)->toBe('Ghana'); // Default value
    });

    test('creates DTO from request data', function () {
        $requestData = [
            'business_name' => 'Test Pharmacy',
            'contact_name' => 'John Doe',
            'email' => 'john@testpharmacy.com',
            'phone' => '0244123456',
            'description' => 'Leading pharmacy in Accra',
            'city' => 'Accra',
            'country' => 'Ghana',
        ];

        $dto = RegisterVendorDTO::fromRequest($requestData);

        expect($dto->businessName)->toBe('Test Pharmacy')
            ->and($dto->phone)->toBe('0244123456')
            ->and($dto->city)->toBe('Accra');
    });

    test('converts DTO to array with required fields', function () {
        $dto = new RegisterVendorDTO(
            tenantId: null,
            businessName: 'Test Pharmacy',
            contactName: 'John Doe',
            email: 'john@testpharmacy.com'
        );

        $array = $dto->toArray();

        expect($array)->toHaveKeys(['business_name', 'contact_name', 'email', 'status'])
            ->and($array['status'])->toBe('pending')
            ->and($array['country'])->toBe('Ghana');
    });

    test('includes optional fields in array when provided', function () {
        $dto = new RegisterVendorDTO(
            tenantId: null,
            businessName: 'Test Pharmacy',
            contactName: 'John Doe',
            email: 'john@testpharmacy.com',
            phone: '0244123456',
            commissionRate: 12.5,
            commissionType: 'percentage'
        );

        $array = $dto->toArray();

        expect($array['phone'])->toBe('0244123456')
            ->and($array['commission_rate'])->toBe(12.5)
            ->and($array['commission_type'])->toBe('percentage');
    });

    test('validates successfully with valid data', function () {
        $dto = new RegisterVendorDTO(
            tenantId: null,
            businessName: 'Test Pharmacy',
            contactName: 'John Doe',
            email: 'john@testpharmacy.com'
        );

        expect($dto->validate())->toBeTrue();
    });

    test('validation fails with empty required fields', function () {
        $dto = new RegisterVendorDTO(
            tenantId: null,
            businessName: '', // Empty required field
            contactName: 'John Doe',
            email: 'john@testpharmacy.com'
        );

        expect($dto->validate())->toBeFalse();
    });

    test('validation fails with invalid email', function () {
        $dto = new RegisterVendorDTO(
            tenantId: null,
            businessName: 'Test Pharmacy',
            contactName: 'John Doe',
            email: 'invalid-email'
        );

        expect($dto->validate())->toBeFalse();
    });
});

describe('UpdateVendorDTO', function () {
    test('creates DTO with all fields as null by default', function () {
        $dto = new UpdateVendorDTO;

        expect($dto->businessName)->toBeNull()
            ->and($dto->contactName)->toBeNull()
            ->and($dto->phone)->toBeNull();
    });

    test('creates DTO from request data with only provided fields', function () {
        $requestData = [
            'business_name' => 'Updated Pharmacy',
            'phone' => '0244999999',
        ];

        $dto = UpdateVendorDTO::fromRequest($requestData);

        expect($dto->businessName)->toBe('Updated Pharmacy')
            ->and($dto->phone)->toBe('0244999999')
            ->and($dto->city)->toBeNull(); // Not provided
    });

    test('converts DTO to array excluding null values', function () {
        $dto = new UpdateVendorDTO(
            businessName: 'Updated Pharmacy',
            phone: '0244999999'
        );

        $array = $dto->toArray();

        expect($array)->toHaveKeys(['business_name', 'phone'])
            ->and($array)->not->toHaveKey('email')
            ->and($array)->not->toHaveKey('description');
    });

    test('returns empty array when all fields are null', function () {
        $dto = new UpdateVendorDTO;

        $array = $dto->toArray();

        expect($array)->toBeEmpty();
    });

    test('includes metadata fields when provided', function () {
        $settings = ['notifications' => true];
        $metadata = ['last_updated_by' => 'admin'];

        $dto = new UpdateVendorDTO(
            settings: $settings,
            metadata: $metadata
        );

        $array = $dto->toArray();

        expect($array['settings'])->toBe($settings)
            ->and($array['metadata'])->toBe($metadata);
    });
});
