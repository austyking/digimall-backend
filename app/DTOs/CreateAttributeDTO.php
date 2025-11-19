<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object for creating an attribute
 */
final readonly class CreateAttributeDTO
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $handle = null,
        public ?string $section = null,
        public ?int $position = null,
        public ?bool $required = null,
        public ?bool $system = null,
        public ?string $attributeGroupId = null,
        public ?array $configuration = null,
        public ?string $attributeType = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            type: $request->input('type'),
            handle: $request->input('handle'),
            section: $request->input('section'),
            position: $request->input('position'),
            required: $request->boolean('required'),
            system: $request->boolean('system'),
            attributeGroupId: $request->input('attribute_group_id'),
            configuration: $request->input('configuration'),
            attributeType: $request->input('attribute_type'),
        );
    }

    /**
     * Convert to array for repository
     */
    public function toArray(): array
    {
        // Get or create default attribute group
        $attributeGroupId = $this->attributeGroupId ?? $this->getDefaultAttributeGroupId();

        $handle = $this->handle ?? str_replace('-', '_', \Illuminate\Support\Str::slug($this->name));

        return [
            'name' => ['en' => $this->name],
            'type' => $this->convertTypeToClass($this->type),
            'handle' => $handle,
            'section' => $this->section,
            'position' => $this->position ?? 1, // Default position if not provided
            'required' => $this->required,
            'system' => $this->system,
            'attribute_group_id' => $attributeGroupId,
            'attribute_type' => $this->attributeType ?? 'product', // Default to 'product'
            'configuration' => $this->configuration,
        ];
    }

    /**
     * Convert type string to field type class name
     */
    private function convertTypeToClass(string $type): string
    {
        return match ($type) {
            'text' => \Lunar\FieldTypes\Text::class,
            'number' => \Lunar\FieldTypes\Number::class,
            'boolean' => \Lunar\FieldTypes\Toggle::class,
            'select' => \Lunar\FieldTypes\Dropdown::class,
            'multiselect' => \Lunar\FieldTypes\ListField::class,
            'richtext' => \Lunar\FieldTypes\TranslatedText::class,
            'file' => \Lunar\FieldTypes\File::class,
            'image' => \Lunar\FieldTypes\File::class,
            default => \Lunar\FieldTypes\Text::class, // Default fallback
        };
    }

    /**
     * Get default attribute group ID
     */
    private function getDefaultAttributeGroupId(): int
    {
        $group = \Lunar\Models\AttributeGroup::firstOrCreate([
            'handle' => 'default',
        ], [
            'name' => 'Default Attributes',
            'handle' => 'default',
            'position' => 1,
            'attributable_type' => 'Lunar\\Models\\Product', // Required field
        ]);

        return $group->id;
    }
}
