<?php

namespace App\Compatibility\Eds;

/**
 * Renders a section as a top-level <div> containing default content and blocks.
 * Browser decorateSections() later wraps/augments this markup.
 */
final class EdsSectionRenderer
{
    public function __construct(
        private readonly EdsBlockRenderer $blockRenderer = new EdsBlockRenderer,
        private readonly EdsDefaultContentRenderer $contentRenderer = new EdsDefaultContentRenderer,
    ) {}

    /**
     * @param  array{children?: array<int, mixed>, metadata?: array<string, mixed>}  $section
     */
    public function render(array $section): string
    {
        $inner = '';
        foreach ($section['children'] ?? [] as $child) {
            if (! is_array($child)) {
                continue;
            }
            $type = (string) ($child['type'] ?? '');
            if ($type === 'block') {
                $inner .= $this->blockRenderer->render($child);
            } else {
                $inner .= $this->contentRenderer->renderNode($child);
            }
        }

        $attrs = $this->sectionAttributes($section['metadata'] ?? []);

        return '<div'.$attrs.'>'.$inner.'</div>';
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function sectionAttributes(array $metadata): string
    {
        $attrs = '';
        $classes = [];

        if (isset($metadata['style'])) {
            $styles = is_array($metadata['style']) ? $metadata['style'] : [$metadata['style']];
            foreach ($styles as $style) {
                $class = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $style)) ?? '';
                if ($class !== '') {
                    $classes[] = $class;
                }
            }
        }

        if ($classes !== []) {
            $attrs .= ' class="'.htmlspecialchars(implode(' ', $classes), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
        }

        if (isset($metadata['id']) && is_string($metadata['id']) && $metadata['id'] !== '') {
            $id = preg_replace('/[^A-Za-z0-9_:-]/', '', $metadata['id']) ?? '';
            if ($id !== '') {
                $attrs .= ' id="'.htmlspecialchars($id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
            }
        }

        foreach ($metadata as $key => $value) {
            if (in_array($key, ['style', 'id'], true)) {
                continue;
            }
            if (! is_scalar($value)) {
                continue;
            }
            $dataKey = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $key)) ?? '';
            if ($dataKey === '') {
                continue;
            }
            $attrs .= ' data-'.$dataKey.'="'.htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
        }

        return $attrs;
    }
}
