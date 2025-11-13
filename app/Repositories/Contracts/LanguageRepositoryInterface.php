<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use Lunar\Models\Language;

interface LanguageRepositoryInterface
{
    /**
     * Find a language by ID.
     */
    public function find(int $id): ?Language;

    /**
     * Get all languages.
     */
    public function all(): Collection;

    /**
     * Find language by code.
     */
    public function findByCode(string $code): ?Language;

    /**
     * Get default language.
     */
    public function getDefault(): ?Language;
}
