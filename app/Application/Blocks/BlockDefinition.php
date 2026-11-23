<?php

namespace App\Application\Blocks;

final class BlockDefinition
{
    /**
     * @param  array<string, mixed>|null  $rawModel
     * @param  array<string, mixed>|null  $normalizedModel
     * @param  array<string, mixed>|null  $authorManifest
     * @param  array<string, mixed>|null  $initialStructure
     * @param  list<string>  $variants
     * @param  list<string>  $warnings
     * @param  list<array<string, mixed>>  $fields
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly ?string $js,
        public readonly ?string $css,
        public readonly ?array $rawModel,
        public readonly ?array $normalizedModel,
        public readonly string $compatibility,
        public readonly array $warnings = [],
        public readonly string $category = 'uncategorized',
        public readonly string $description = '',
        public readonly string $icon = 'box',
        public readonly array $variants = [],
        public readonly array $fields = [],
        public readonly ?array $initialStructure = null,
        public readonly ?array $authorManifest = null,
        public readonly array $aliases = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'js' => $this->js,
            'css' => $this->css,
            'category' => $this->category,
            'description' => $this->description,
            'icon' => $this->icon,
            'variants' => $this->variants,
            'fields' => $this->fields,
            'initialStructure' => $this->initialStructure,
            'aliases' => $this->aliases,
            'rawModel' => $this->rawModel,
            'normalizedModel' => $this->normalizedModel,
            'authorManifest' => $this->authorManifest,
            'compatibility' => $this->compatibility,
            'warnings' => $this->warnings,
        ];
    }
}
