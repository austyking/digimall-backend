<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;
use Lunar\FieldTypes\TranslatedText;

/**
 * Data Transfer Object for creating a category
 */
final readonly class CreateCategoryDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?int $parentId = null,
        public ?int $collectionGroupId = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
            parentId: $request->input('parent_id'),
            collectionGroupId: $request->input('collection_group_id') ? (int) $request->input('collection_group_id') : null,
        );
    }

    /**
     * Convert to array for repository
     */
    public function toArray(): array
    {
        // Get or create default collection group
        $collectionGroupId = $this->collectionGroupId ?? $this->getDefaultCollectionGroupId();

        $attributeData = [
            'name' => new TranslatedText(['en' => $this->name]),
        ];

        if ($this->description) {
            $attributeData['description'] = new TranslatedText(['en' => $this->description]);
        }

        return [
            'attribute_data' => $attributeData,
            'collection_group_id' => $collectionGroupId,
            'parent_id' => $this->parentId,
        ];
    }

    /**
     * Get default collection group ID
     */
    private function getDefaultCollectionGroupId(): int
    {
        $group = \Lunar\Models\CollectionGroup::firstOrCreate([
            'handle' => 'main-catalogue',
        ], [
            'name' => 'Main Catalogue',
            'handle' => 'main-catalogue',
        ]);

        return $group->id;
    }
}
