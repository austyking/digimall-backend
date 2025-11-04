<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class CustomerService
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Find a customer by ID.
     */
    public function findById(string $id): ?Customer
    {
        return $this->customerRepository->find($id);
    }

    /**
     * Find customer by membership number.
     */
    public function findByMembershipNumber(string $membershipNumber): ?Customer
    {
        return $this->customerRepository->findByMembershipNumber($membershipNumber);
    }

    /**
     * Find customer by user ID.
     */
    public function findByUserId(string $userId): ?Customer
    {
        return $this->customerRepository->findByUserId($userId);
    }

    /**
     * Get all customers for a tenant.
     */
    public function getAllCustomers(?int $limit = null): Collection
    {
        return $this->customerRepository->getByTenant($limit);
    }

    /**
     * Search customers by membership number.
     */
    public function searchCustomers(string $query, ?int $limit = null): Collection
    {
        return $this->customerRepository->search($query, $limit);
    }

    /**
     * Fetch customer data from external association API.
     */
    public function fetchCustomerFromAssociation(string $membershipNumber, string $tenantId): ?array
    {
        return $this->customerRepository->fetchFromAssociation($membershipNumber, $tenantId);
    }

    /**
     * Verify customer with association API.
     */
    public function verifyWithAssociation(string $membershipNumber, string $tenantId): bool
    {
        return $this->customerRepository->verifyWithAssociation($membershipNumber, $tenantId);
    }

    /**
     * Get hire-purchase eligibility for customer.
     */
    public function getHirePurchaseEligibility(string $membershipNumber, string $tenantId): array
    {
        return $this->customerRepository->getHirePurchaseEligibility($membershipNumber, $tenantId);
    }

    /**
     * Check if customer is eligible for hire-purchase.
     */
    public function isEligibleForHirePurchase(string $membershipNumber, string $tenantId): bool
    {
        return $this->customerRepository->isEligibleForHirePurchase($membershipNumber, $tenantId);
    }

    /**
     * Get complete customer profile (local + API data).
     */
    public function getCustomerProfile(string $membershipNumber, string $tenantId): ?array
    {
        $localCustomer = $this->findByMembershipNumber($membershipNumber);

        if (! $localCustomer) {
            return null;
        }

        $apiData = $this->fetchCustomerFromAssociation($membershipNumber, $tenantId);

        if (! $apiData) {
            return null;
        }

        return [
            'id' => $localCustomer->id,
            'membership_number' => $localCustomer->membership_number,
            'user_id' => $localCustomer->user_id,
            'tenant_id' => $localCustomer->tenant_id,
            'created_at' => $localCustomer->created_at,
            'updated_at' => $localCustomer->updated_at,
            // External data from association API
            'first_name' => $apiData['first_name'] ?? null,
            'last_name' => $apiData['last_name'] ?? null,
            'email' => $apiData['email'] ?? null,
            'phone' => $apiData['phone'] ?? null,
            'status' => $apiData['status'] ?? null,
            'verified_at' => $apiData['verified_at'] ?? null,
        ];
    }

    /**
     * Verify and get customer eligibility details.
     */
    public function verifyAndGetEligibility(string $membershipNumber, string $tenantId): array
    {
        $isVerified = $this->verifyWithAssociation($membershipNumber, $tenantId);

        if (! $isVerified) {
            return [
                'verified' => false,
                'eligible_for_hire_purchase' => false,
                'message' => 'Membership number not verified with association.',
            ];
        }

        $eligibility = $this->getHirePurchaseEligibility($membershipNumber, $tenantId);

        return [
            'verified' => true,
            'eligible_for_hire_purchase' => $this->isEligibleForHirePurchase($membershipNumber, $tenantId),
            'eligibility_details' => $eligibility,
        ];
    }
}
