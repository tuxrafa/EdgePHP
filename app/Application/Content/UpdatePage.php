<?php

namespace App\Application\Content;

use App\Application\Composition\ContentDependencyGraph;
use App\Domain\Content\Page;
use App\Domain\Content\PageRevision;
use Illuminate\Support\Str;

final class UpdatePage
{
    public function __construct(
        private readonly ContentDependencyGraph $graph = new ContentDependencyGraph,
    ) {}

    /**
     * @param  array{
     *   title?: string,
     *   path?: string,
     *   templateId?: string|null,
     *   metadata?: array<string, mixed>,
     *   content?: array<string, mixed>,
     *   expectedRevisionId?: string|null,
     *   message?: string|null
     * }  $input
     * @return array{page: Page, revision: PageRevision}
     */
    public function handle(string $pageId, array $input): array
    {
        $page = Page::query()->find($pageId);
        if (! $page) {
            throw new \RuntimeException('Page not found.', 404);
        }

        if (! empty($input['expectedRevisionId'])
            && $page->draftRevisionId
            && $input['expectedRevisionId'] !== $page->draftRevisionId) {
            throw new \RuntimeException('Revision conflict. Reload and try again.', 409);
        }

        $current = PageRevision::query()->find($page->draftRevisionId);
        $content = $input['content'] ?? ($current?->content ?? ['metadata' => [], 'sections' => []]);
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

        if (isset($input['metadata']) && is_array($input['metadata'])) {
            $content['metadata'] = array_merge(
                is_array($content['metadata'] ?? null) ? $content['metadata'] : [],
                $input['metadata']
            );
        }

        $title = (string) ($input['title']
            ?? $page->title
            ?? ($content['metadata']['title'] ?? 'Untitled'));

        if (isset($input['path'])) {
            $page->path = $this->normalizePath((string) $input['path']);
        }

        if (array_key_exists('templateId', $input)) {
            $page->templateId = $input['templateId'] ?: null;
            if ($page->templateId) {
                $content['templateId'] = $page->templateId;
            }
        }

        $nextNumber = ((int) ($current?->number ?? 0)) + 1;
        $revisionId = (string) Str::ulid();

        $revision = PageRevision::query()->create([
            '_id' => $revisionId,
            'pageId' => $page->id,
            'number' => $nextNumber,
            'content' => $content,
            'authorId' => $input['authorId'] ?? null,
            'message' => $input['message'] ?? 'Author save',
        ]);

        $page->title = $title;
        $page->draftRevisionId = $revisionId;
        $page->metadata = is_array($content['metadata'] ?? null) ? $content['metadata'] : [];
        $page->status = $page->status === 'published' ? 'published' : 'draft';
        $page->save();

        $this->graph->indexPageRevision($page, $revision);

        return ['page' => $page->fresh(), 'revision' => $revision];
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
                if (! is_array($child) || ($child['type'] ?? '') !== 'block') {
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
