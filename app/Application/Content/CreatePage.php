<?php

namespace App\Application\Content;

use App\Application\Composition\ContentDependencyGraph;
use App\Application\Composition\TemplateResolver;
use App\Application\Composition\RenderContext;
use App\Domain\Content\Page;
use App\Domain\Content\PageRevision;
use Illuminate\Support\Str;

final class CreatePage
{
    public function __construct(
        private readonly TemplateResolver $templates = new TemplateResolver,
        private readonly ContentDependencyGraph $graph = new ContentDependencyGraph,
    ) {}

    /**
     * @param  array{
     *   siteId?: string,
     *   locale?: string,
     *   path: string,
     *   title?: string,
     *   templateId?: string|null,
     *   metadata?: array<string, mixed>,
     *   sections?: array<int, mixed>,
     *   content?: array<string, mixed>
     * }  $input
     * @return array{page: Page, revision: PageRevision}
     */
    public function handle(array $input): array
    {
        $path = $this->normalizePath((string) ($input['path'] ?? ''));
        if ($path === '') {
            throw new \InvalidArgumentException('path is required.');
        }

        $title = (string) ($input['title'] ?? 'Untitled');
        $templateId = isset($input['templateId']) && $input['templateId'] !== ''
            ? (string) $input['templateId']
            : null;

        if (isset($input['content']) && is_array($input['content'])) {
            $content = $input['content'];
        } elseif ($templateId) {
            $content = $this->seedFromTemplate(
                $templateId,
                $title,
                is_array($input['metadata'] ?? null) ? $input['metadata'] : ['title' => $title],
                (string) ($input['siteId'] ?? 'main'),
            );
        } else {
            $content = [
                'metadata' => $input['metadata'] ?? ['title' => $title],
                'sections' => $input['sections'] ?? [],
            ];
        }

        if (! is_array($content)) {
            throw new \InvalidArgumentException('content must be an object.');
        }

        if (isset($content['sections'])) {
            $this->assertNoNestedBlocks($content['sections'] ?? []);
        }
        if (isset($content['slots']) && is_array($content['slots'])) {
            foreach ($content['slots'] as $slot) {
                if (is_array($slot)) {
                    $this->assertNoNestedBlocks($slot['sections'] ?? []);
                }
            }
        }

        $pageId = (string) Str::ulid();
        $revisionId = (string) Str::ulid();
        $title = (string) ($input['title']
            ?? ($content['metadata']['title'] ?? null)
            ?? 'Untitled');

        $revision = PageRevision::query()->create([
            '_id' => $revisionId,
            'pageId' => $pageId,
            'number' => 1,
            'content' => $content,
            'authorId' => $input['authorId'] ?? null,
            'message' => $input['message'] ?? 'Initial revision',
        ]);

        $page = Page::query()->create([
            '_id' => $pageId,
            'siteId' => (string) ($input['siteId'] ?? 'main'),
            'locale' => (string) ($input['locale'] ?? 'pt-BR'),
            'path' => $path,
            'title' => $title,
            'status' => 'draft',
            'templateId' => $templateId ?? ($content['templateId'] ?? null),
            'draftRevisionId' => $revisionId,
            'publishedRevisionId' => null,
            'metadata' => is_array($content['metadata'] ?? null) ? $content['metadata'] : [],
        ]);

        $this->graph->indexPageRevision($page, $revision);

        return ['page' => $page, 'revision' => $revision];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function seedFromTemplate(string $templateId, string $title, array $metadata, string $siteId): array
    {
        $ctx = new RenderContext(mode: 'preview', siteId: $siteId);
        $resolved = $this->templates->resolve($templateId, $ctx);
        $slots = [];
        $initial = $resolved['initialContent'];

        foreach ($resolved['layout'] as $node) {
            if (! is_array($node) || ($node['type'] ?? '') !== 'slot') {
                continue;
            }
            $slotId = (string) ($node['id'] ?? '');
            if ($slotId === '') {
                continue;
            }
            // Initial content is copied once at page creation — never linked.
            if (isset($initial[$slotId]) && is_array($initial[$slotId])) {
                $slots[$slotId] = $initial[$slotId];
            } else {
                $slots[$slotId] = [
                    'sections' => [
                        [
                            'id' => 'section-'.Str::lower(Str::random(6)),
                            'type' => 'section',
                            'metadata' => ['slotId' => $slotId],
                            'children' => [],
                        ],
                    ],
                ];
            }
        }

        $metadata['title'] = $metadata['title'] ?? $title;

        return [
            'metadata' => $metadata,
            'templateId' => $templateId,
            'slots' => $slots,
            'orphanSlots' => [],
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = '/'.ltrim(trim($path), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '/' ? '/' : $path;
    }

    private function assertNoNestedBlocks(mixed $sections): void
    {
        if (! is_array($sections)) {
            return;
        }

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }
            foreach ($section['children'] ?? [] as $child) {
                if (! is_array($child)) {
                    continue;
                }
                if (($child['type'] ?? '') !== 'block') {
                    continue;
                }
                foreach ($child['rows'] ?? [] as $row) {
                    foreach (($row['cells'] ?? []) as $cell) {
                        foreach (($cell['content'] ?? []) as $node) {
                            if (is_array($node) && ($node['type'] ?? '') === 'block') {
                                throw new \InvalidArgumentException('Nested blocks are not allowed.');
                            }
                        }
                    }
                }
            }
        }
    }
}
