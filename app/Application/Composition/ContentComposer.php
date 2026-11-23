<?php

namespace App\Application\Composition;

use App\Domain\Content\Page;
use App\Domain\Content\PageRevision;

/**
 * Produces an immutable resolved page document ({metadata, sections}) for EdsPageRenderer.
 * Never mutates the stored page/template/fragment documents.
 */
final class ContentComposer
{
    public function __construct(
        private readonly TemplateResolver $templates = new TemplateResolver,
        private readonly FragmentResolver $fragments = new FragmentResolver,
    ) {}

    /**
     * @return array{
     *   content: array{metadata: array<string, mixed>, sections: list<array<string, mixed>>},
     *   dependencies: list<array<string, mixed>>,
     *   templateId: string|null,
     *   templateRevisionId: string|null
     * }
     */
    public function composePage(
        Page $page,
        PageRevision $revision,
        RenderContext $context,
    ): array {
        $raw = is_array($revision->content) ? $revision->content : [];
        $metadata = is_array($raw['metadata'] ?? null) ? $raw['metadata'] : [];
        if (! isset($metadata['title'])) {
            $metadata['title'] = $page->title;
        }

        $templateId = (string) ($page->templateId ?? $raw['templateId'] ?? '');
        if ($templateId === '') {
            // Legacy / blank pages: sections are the document.
            $sections = is_array($raw['sections'] ?? null) ? $raw['sections'] : [];
            $sections = $this->expandSectionTree($sections, $context);

            return [
                'content' => [
                    'metadata' => $metadata,
                    'sections' => $sections,
                ],
                'dependencies' => $context->dependencies,
                'templateId' => null,
                'templateRevisionId' => null,
            ];
        }

        $resolvedTemplate = $this->templates->resolve(
            $templateId,
            $context,
            isset($raw['templateRevisionId']) ? (string) $raw['templateRevisionId'] : null,
        );

        $slots = is_array($raw['slots'] ?? null) ? $raw['slots'] : [];
        $orphanSlots = is_array($raw['orphanSlots'] ?? null) ? $raw['orphanSlots'] : [];
        $composedSections = [];

        foreach ($resolvedTemplate['layout'] as $node) {
            if (! is_array($node)) {
                continue;
            }
            $composedSections = [
                ...$composedSections,
                ...$this->expandLayoutNode($node, $slots, $orphanSlots, $context),
            ];
        }

        return [
            'content' => [
                'metadata' => $metadata,
                'sections' => $composedSections,
            ],
            'dependencies' => $context->dependencies,
            'templateId' => $resolvedTemplate['template']->id,
            'templateRevisionId' => $resolvedTemplate['revision']->id,
        ];
    }

    /**
     * Compose a template draft preview (slots empty / initial).
     *
     * @param  array<string, mixed>  $slotOverrides
     * @return array{content: array{metadata: array<string, mixed>, sections: list<array<string, mixed>>}, dependencies: list<array<string, mixed>>}
     */
    public function composeTemplatePreview(
        string $templateId,
        RenderContext $context,
        array $slotOverrides = [],
        ?string $title = null,
    ): array {
        $resolved = $this->templates->resolve($templateId, $context);
        $sections = [];
        foreach ($resolved['layout'] as $node) {
            if (! is_array($node)) {
                continue;
            }
            $sections = [
                ...$sections,
                ...$this->expandLayoutNode($node, $slotOverrides, [], $context),
            ];
        }

        return [
            'content' => [
                'metadata' => [
                    'title' => $title ?? $resolved['template']->name.' (template preview)',
                ],
                'sections' => $sections,
            ],
            'dependencies' => $context->dependencies,
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $slots
     * @param  array<string, mixed>  $orphanSlots
     * @return list<array<string, mixed>>
     */
    private function expandLayoutNode(
        array $node,
        array $slots,
        array $orphanSlots,
        RenderContext $context,
    ): array {
        $type = (string) ($node['type'] ?? '');

        return match ($type) {
            'slot' => $this->expandSlot($node, $slots, $context),
            'fragmentReference' => $this->expandFragmentReference($node, $context),
            'section' => [$this->normalizeSection($node, $context)],
            'block' => [[
                'id' => $node['id'] ?? null,
                'type' => 'section',
                'metadata' => ['region' => $node['region'] ?? 'main', 'locked' => true],
                'children' => [$node],
            ]],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $slot
     * @param  array<string, mixed>  $slots
     * @return list<array<string, mixed>>
     */
    private function expandSlot(array $slot, array $slots, RenderContext $context): array
    {
        $slotId = (string) ($slot['id'] ?? '');
        if ($slotId === '') {
            return [];
        }

        $payload = $slots[$slotId] ?? null;
        $sections = [];
        if (is_array($payload)) {
            $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];
        }

        if ($sections === [] && ! empty($slot['required'])) {
            // Keep a visible empty section so structure remains stable in preview.
            $sections = [[
                'id' => 'empty-'.$slotId,
                'type' => 'section',
                'metadata' => [
                    'slotId' => $slotId,
                    'label' => $slot['label'] ?? $slotId,
                    'placeholder' => true,
                ],
                'children' => [],
            ]];
        }

        $expanded = $this->expandSectionTree($sections, $context);

        return array_map(function (array $section) use ($slotId, $slot) {
            $meta = is_array($section['metadata'] ?? null) ? $section['metadata'] : [];
            $meta['slotId'] = $slotId;
            if (! empty($slot['label'])) {
                $meta['slotLabel'] = $slot['label'];
            }
            $section['metadata'] = $meta;

            return $section;
        }, $expanded);
    }

    /**
     * @param  array<string, mixed>  $ref
     * @return list<array<string, mixed>>
     */
    private function expandFragmentReference(array $ref, RenderContext $context): array
    {
        $fragmentId = (string) ($ref['fragmentId'] ?? '');
        if ($fragmentId === '') {
            return [];
        }

        $mode = (string) ($ref['mode'] ?? 'linked');
        $variation = (string) ($ref['variation'] ?? 'default');
        $pinned = $mode === 'pinned' ? ($ref['pinnedRevisionId'] ?? null) : null;

        // Detached content is already expanded into the host document at insert time.
        if ($mode === 'detached') {
            $sections = is_array($ref['sections'] ?? null) ? $ref['sections'] : [];

            return $this->expandSectionTree($sections, $context);
        }

        // Resolve against the current stack, then push for nested expansion only.
        $resolved = $this->fragments->resolve(
            $fragmentId,
            $variation,
            $context,
            is_string($pinned) ? $pinned : null,
        );

        $childContext = $context->withPushed($fragmentId.'@'.$variation);
        $childContext->dependencies = [];
        $childContext->resolvedFragments = $context->resolvedFragments;

        $sections = $this->expandSectionTree($resolved['sections'], $childContext);

        $context->dependencies = [
            ...$context->dependencies,
            ...$childContext->dependencies,
        ];
        $context->resolvedFragments = [
            ...$context->resolvedFragments,
            ...$childContext->resolvedFragments,
        ];

        return array_map(function (array $section) use ($ref, $fragmentId) {
            $meta = is_array($section['metadata'] ?? null) ? $section['metadata'] : [];
            $meta['fragmentId'] = $fragmentId;
            $meta['fragmentRefId'] = $ref['id'] ?? null;
            $meta['fragmentMode'] = $ref['mode'] ?? 'linked';
            $meta['locked'] = ($ref['mode'] ?? 'linked') !== 'detached';
            if (! empty($ref['region'])) {
                $meta['region'] = $ref['region'];
            }
            $section['metadata'] = $meta;

            return $section;
        }, $sections);
    }

    /**
     * Expand fragmentReference nodes that appear inside section children.
     *
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function expandSectionTree(array $sections, RenderContext $context): array
    {
        $out = [];
        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            if (($section['type'] ?? '') === 'fragmentReference') {
                $out = [...$out, ...$this->expandFragmentReference($section, $context)];

                continue;
            }

            $children = is_array($section['children'] ?? null) ? $section['children'] : [];
            $nextChildren = [];
            foreach ($children as $child) {
                if (! is_array($child)) {
                    continue;
                }
                if (($child['type'] ?? '') === 'fragmentReference') {
                    // Fragment refs at child level expand to sections; hoist them after this section.
                    $out = [...$out, ...$this->expandFragmentReference($child, $context)];

                    continue;
                }
                $nextChildren[] = $child;
            }
            $section['children'] = $nextChildren;
            $out[] = $this->normalizeSection($section, $context);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function normalizeSection(array $section, RenderContext $context): array
    {
        $section['type'] = $section['type'] ?? 'section';
        if (! isset($section['children']) || ! is_array($section['children'])) {
            $section['children'] = [];
        }

        return $section;
    }
}
