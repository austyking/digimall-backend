<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;
use Lunar\FieldTypes\TranslatedText;

/**
 * Data Transfer Object for updating a category
 */
final readonly class UpdateCategoryDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?int $parentId = null,
        public ?string $collectionGroupId = null,
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
            collectionGroupId: $request->input('collection_group_id'),
        );
    }

    /**
     * Convert to array for repository
     */
    public function toArray(): array
    {
        $attributeData = [
            'name' => new TranslatedText(['en' => $this->name]),
        ];

        if ($this->description) {
            $attributeData['description'] = new TranslatedText(['en' => $this->description]);
        }

        $data = [
            'attribute_data' => $attributeData,
        ];

        if ($this->parentId !== null) {
            $data['parent_id'] = $this->parentId;
        }

        if ($this->collectionGroupId !== null) {
            $data['collection_group_id'] = $this->collectionGroupId;
        }

        return $data;
    }
}
