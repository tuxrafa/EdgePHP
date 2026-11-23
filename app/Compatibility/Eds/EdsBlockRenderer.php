<?php

namespace App\Compatibility\Eds;

/**
 * Emits EDS block *input* markup (nested div rows/cells).
 * Does not apply browser decoration that belongs to block JS.
 *
 * @see https://www.aem.live/developer/markup-sections-blocks
 */
final class EdsBlockRenderer
{
    public function __construct(
        private readonly EdsDefaultContentRenderer $contentRenderer = new EdsDefaultContentRenderer,
    ) {}

    /**
     * @param  array{name: string, classes?: array<int, string>, rows?: array<int, mixed>}  $block
     */
    public function render(array $block): string
    {
        $name = $this->sanitizeBlockName((string) ($block['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Block name is required.');
        }

        $classes = [$name];
        foreach ($block['classes'] ?? [] as $class) {
            $sanitized = $this->sanitizeClass((string) $class);
            if ($sanitized !== '' && ! in_array($sanitized, $classes, true)) {
                $classes[] = $sanitized;
            }
        }

        $rowsHtml = '';
        foreach ($block['rows'] ?? [] as $row) {
            $rowsHtml .= $this->renderRow(is_array($row) ? $row : []);
        }

        $classAttr = htmlspecialchars(implode(' ', $classes), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<div class="'.$classAttr.'">'.$rowsHtml.'</div>';
    }

    /**
     * @param  array{cells?: array<int, mixed>}  $row
     */
    private function renderRow(array $row): string
    {
        $cellsHtml = '';
        foreach ($row['cells'] ?? [] as $cell) {
            $cellsHtml .= $this->renderCell(is_array($cell) ? $cell : []);
        }

        return '<div>'.$cellsHtml.'</div>';
    }

    /**
     * @param  array{content?: array<int, mixed>, html?: string}  $cell
     */
    private function renderCell(array $cell): string
    {
        if (isset($cell['html']) && is_string($cell['html'])) {
            // Escape hatch for fixtures already holding sanitized EDS cell HTML.
            // Slice 1 still runs through the sanitizer.
            $inner = $this->contentRenderer->sanitizeFragment($cell['html']);
        } else {
            $inner = $this->contentRenderer->renderNodes($cell['content'] ?? []);
        }

        return '<div>'.$inner.'</div>';
    }

    private function sanitizeBlockName(string $name): string
    {
        $name = strtolower(trim($name));

        return preg_replace('/[^a-z0-9-]/', '', $name) ?? '';
    }

    private function sanitizeClass(string $class): string
    {
        $class = strtolower(trim($class));

        return preg_replace('/[^a-z0-9-]/', '', $class) ?? '';
    }
}
