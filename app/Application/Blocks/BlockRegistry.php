<?php

namespace App\Application\Blocks;

/**
 * Discovers EDS-shaped blocks under blocks/{name}/ without mutating them.
 */
final class BlockRegistry
{
    public function __construct(
        private readonly string $blocksPath = '',
    ) {}

    /**
     * @return list<BlockDefinition>
     */
    public function scan(): array
    {
        $root = $this->blocksPath !== '' ? $this->blocksPath : base_path('blocks');
        if (! is_dir($root)) {
            return [];
        }

        $definitions = [];
        foreach (scandir($root) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $root.DIRECTORY_SEPARATOR.$entry;
            if (! is_dir($dir)) {
                continue;
            }
            $def = $this->scanBlock($entry, $dir);
            if ($def !== null) {
                $definitions[] = $def;
            }
        }

        usort($definitions, function (BlockDefinition $a, BlockDefinition $b) {
            $cat = strcmp($a->category, $b->category);
            if ($cat !== 0) {
                return $cat;
            }

            return strcmp($a->label, $b->label);
        });

        return $definitions;
    }

    public function find(string $name): ?BlockDefinition
    {
        $needle = strtolower($name);
        foreach ($this->scan() as $definition) {
            if ($definition->name === $needle) {
                return $definition;
            }
            foreach ($definition->aliases as $alias) {
                if (strtolower((string) $alias) === $needle) {
                    return $definition;
                }
            }
        }

        return null;
    }

    private function scanBlock(string $name, string $dir): ?BlockDefinition
    {
        $name = strtolower($name);
        if (! preg_match('/^[a-z0-9-]+$/', $name)) {
            return null;
        }

        $jsPath = $dir.DIRECTORY_SEPARATOR.$name.'.js';
        $cssPath = $dir.DIRECTORY_SEPARATOR.$name.'.css';
        $hasJs = is_file($jsPath);
        $hasCss = is_file($cssPath);
        if (! $hasJs && ! $hasCss) {
            return null;
        }

        $warnings = [];
        $authorManifest = $this->loadJson($dir.DIRECTORY_SEPARATOR.$name.'.author.json', $warnings);

        $rawModel = null;
        foreach (['_'.$name.'.json', $name.'.json'] as $modelFile) {
            $decoded = $this->loadJson($dir.DIRECTORY_SEPARATOR.$modelFile, $warnings);
            if ($decoded !== null) {
                $rawModel = $decoded;
                break;
            }
        }

        $normalized = $rawModel !== null ? $this->normalizeModel($rawModel, $warnings) : null;
        if ($authorManifest !== null) {
            $normalized = $this->normalizeAuthorManifest($authorManifest, $warnings);
        }

        $label = (string) ($authorManifest['label'] ?? ucwords(str_replace('-', ' ', $name)));
        $category = (string) ($authorManifest['category'] ?? 'uncategorized');
        $description = (string) ($authorManifest['description'] ?? '');
        $icon = (string) ($authorManifest['icon'] ?? 'box');
        $variants = array_values(array_filter(
            is_array($authorManifest['variants'] ?? null) ? $authorManifest['variants'] : [],
            fn ($v) => is_string($v) && $v !== ''
        ));
        $fields = is_array($authorManifest['fields'] ?? null) ? $authorManifest['fields'] : ($normalized['fields'] ?? []);
        $initial = is_array($authorManifest['initialStructure'] ?? null) ? $authorManifest['initialStructure'] : null;
        $aliases = array_values(array_filter(
            is_array($authorManifest['aliases'] ?? null) ? $authorManifest['aliases'] : [],
            fn ($v) => is_string($v)
        ));

        $compatibility = (string) ($authorManifest['compatibility'] ?? ($hasJs ? 'EDS-C1' : 'EDS-C0'));

        return new BlockDefinition(
            name: $name,
            label: $label,
            js: $hasJs ? '/blocks/'.$name.'/'.$name.'.js' : null,
            css: $hasCss ? '/blocks/'.$name.'/'.$name.'.css' : null,
            rawModel: $rawModel,
            normalizedModel: $normalized,
            compatibility: $compatibility,
            warnings: $warnings,
            category: $category,
            description: $description,
            icon: $icon,
            variants: $variants,
            fields: is_array($fields) ? $fields : [],
            initialStructure: $initial,
            authorManifest: $authorManifest,
            aliases: $aliases,
        );
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, mixed>|null
     */
    private function loadJson(string $path, array &$warnings): ?array
    {
        if (! is_file($path)) {
            return null;
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            $warnings[] = 'Invalid JSON: '.basename($path);

            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function normalizeAuthorManifest(array $raw, array &$warnings): array
    {
        foreach (array_keys($raw) as $key) {
            if (! in_array($key, [
                'schemaVersion', 'name', 'label', 'category', 'description', 'icon',
                'variants', 'fields', 'initialStructure', 'aliases', 'compatibility',
            ], true)) {
                $warnings[] = "Unrecognized author manifest property retained: {$key}";
            }
        }

        return [
            'fields' => is_array($raw['fields'] ?? null) ? $raw['fields'] : [],
            'rawKeys' => array_keys($raw),
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function normalizeModel(array $raw, array &$warnings): array
    {
        $fields = [];
        $sourceFields = $raw['fields'] ?? $raw;
        if (! is_array($sourceFields)) {
            $warnings[] = 'Model fields are not an array; stored raw only.';

            return ['fields' => [], 'unsupported' => array_keys($raw)];
        }

        foreach ($sourceFields as $key => $field) {
            if (! is_array($field)) {
                continue;
            }
            $fields[] = [
                'name' => (string) ($field['name'] ?? $key),
                'label' => (string) ($field['label'] ?? $field['name'] ?? $key),
                'component' => (string) ($field['component'] ?? $field['type'] ?? 'text-input'),
                'valueType' => (string) ($field['valueType'] ?? 'string'),
                'raw' => $field,
            ];
        }

        return [
            'fields' => $fields,
            'rawKeys' => array_keys($raw),
        ];
    }
}
