<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Support\Collection;
use Lunar\Models\Language;

final class LanguageRepository implements LanguageRepositoryInterface
{
    /**
     * Find a language by ID.
     */
    public function find(int $id): ?Language
    {
        return Language::query()->find($id);
    }

    /**
     * Get all languages.
     */
    public function all(): Collection
    {
        return Language::query()->get();
    }

    /**
     * Find language by code.
     */
    public function findByCode(string $code): ?Language
    {
        return Language::query()->where('code', $code)->first();
    }

    /**
     * Get default language.
     */
    public function getDefault(): ?Language
    {
        return Language::query()->where('default', true)->first();
    }
}
