<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Support\Collection;
use Lunar\Models\Language;

final readonly class LanguageService
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository
    ) {}

    /**
     * Find a language by ID.
     */
    public function find(int $id): ?Language
    {
        return $this->languageRepository->find($id);
    }

    /**
     * Get all languages.
     */
    public function all(): Collection
    {
        return $this->languageRepository->all();
    }

    /**
     * Find language by code.
     */
    public function findByCode(string $code): ?Language
    {
        return $this->languageRepository->findByCode($code);
    }

    /**
     * Get default language.
     */
    public function getDefault(): ?Language
    {
        return $this->languageRepository->getDefault();
    }
}
