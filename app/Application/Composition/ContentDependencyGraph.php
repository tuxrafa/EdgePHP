<?php

namespace App\Application\Composition;

use App\Domain\Content\ContentReference;
use App\Domain\Content\Fragment;
use App\Domain\Content\FragmentRevision;
use App\Domain\Content\Page;
use App\Domain\Content\PageRevision;
use App\Domain\Content\Template;
use App\Domain\Content\TemplateRevision;
use Illuminate\Support\Str;

final class ContentDependencyGraph
{
    /**
     * Rebuild materialized references for a host document revision.
     *
     * @param  list<array{type: string, id: string, revisionId?: string|null, variation?: string|null, mode?: string|null, path?: string|null}>  $deps
     */
    public function replaceFor(
        string $siteId,
        string $fromType,
        string $fromId,
        ?string $fromRevisionId,
        array $deps,
    ): void {
        ContentReference::query()
            ->where('fromType', $fromType)
            ->where('fromId', $fromId)
            ->delete();

        foreach ($deps as $dep) {
            if (! is_array($dep) || empty($dep['id']) || empty($dep['type'])) {
                continue;
            }
            ContentReference::query()->create([
                '_id' => (string) Str::ulid(),
                'siteId' => $siteId,
                'fromType' => $fromType,
                'fromId' => $fromId,
                'fromRevisionId' => $fromRevisionId,
                'toType' => $dep['type'],
                'toId' => $dep['id'],
                'toVariation' => $dep['variation'] ?? null,
                'mode' => $dep['mode'] ?? 'linked',
                'pinnedRevisionId' => $dep['revisionId'] ?? null,
                'path' => $dep['path'] ?? null,
            ]);
        }
    }

    /**
     * Extract fragment references from template layout or page content.
     *
     * @param  array<string, mixed>  $document
     * @return list<array<string, mixed>>
     */
    public function extractFromDocument(array $document): array
    {
        $found = [];
        $walk = function ($node, string $path) use (&$walk, &$found): void {
            if (! is_array($node)) {
                return;
            }
            if (($node['type'] ?? '') === 'fragmentReference' && ! empty($node['fragmentId'])) {
                $found[] = [
                    'type' => 'fragment',
                    'id' => $node['fragmentId'],
                    'variation' => $node['variation'] ?? 'default',
                    'mode' => $node['mode'] ?? 'linked',
                    'revisionId' => $node['pinnedRevisionId'] ?? null,
                    'path' => $path,
                ];
            }
            foreach ($node as $key => $value) {
                if (is_array($value)) {
                    $walk($value, $path.'/'.$key);
                }
            }
        };

        $walk($document, '$');

        return $found;
    }

    /**
     * @return array{
     *   templates: list<array<string, mixed>>,
     *   pages: list<array<string, mixed>>,
     *   fragments: list<array<string, mixed>>,
     *   indirectPageCount: int
     * }
     */
    public function usageFor(string $toType, string $toId): array
    {
        $direct = ContentReference::query()
            ->where('toType', $toType)
            ->where('toId', $toId)
            ->get();

        $templates = [];
        $pages = [];
        $fragments = [];

        foreach ($direct as $ref) {
            $row = [
                'id' => $ref->fromId,
                'mode' => $ref->mode,
                'revisionId' => $ref->fromRevisionId,
            ];
            if ($ref->fromType === 'template') {
                $tpl = Template::query()->find($ref->fromId);
                $row['name'] = $tpl?->name;
                $row['slug'] = $tpl?->slug;
                $templates[] = $row;
            } elseif ($ref->fromType === 'page') {
                $page = Page::query()->find($ref->fromId);
                $row['title'] = $page?->title;
                $row['path'] = $page?->path;
                $pages[] = $row;
            } elseif ($ref->fromType === 'fragment') {
                $frag = Fragment::query()->find($ref->fromId);
                $row['name'] = $frag?->name;
                $row['slug'] = $frag?->slug;
                $fragments[] = $row;
            }
        }

        $indirectPageCount = 0;
        if ($toType === 'fragment') {
            foreach ($templates as $tpl) {
                $indirectPageCount += Page::query()
                    ->where('templateId', $tpl['id'])
                    ->count();
            }
        }

        return [
            'templates' => $templates,
            'pages' => $pages,
            'fragments' => $fragments,
            'indirectPageCount' => $indirectPageCount,
        ];
    }

    public function pagesUsingTemplate(string $templateId): array
    {
        return Page::query()
            ->where('templateId', $templateId)
            ->get(['_id', 'title', 'path', 'status', 'draftRevisionId', 'publishedRevisionId'])
            ->map(fn (Page $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'path' => $p->path,
                'status' => $p->status,
            ])
            ->all();
    }

    /**
     * Index references after saving a template revision.
     */
    public function indexTemplateRevision(Template $template, TemplateRevision $revision): void
    {
        $deps = $this->extractFromDocument([
            'layout' => $revision->layout ?? [],
        ]);
        $this->replaceFor(
            (string) $template->siteId,
            'template',
            (string) $template->id,
            (string) $revision->id,
            $deps,
        );
    }

    public function indexFragmentRevision(Fragment $fragment, FragmentRevision $revision): void
    {
        $deps = $this->extractFromDocument(
            is_array($revision->content) ? $revision->content : []
        );
        $this->replaceFor(
            (string) $fragment->siteId,
            'fragment',
            (string) $fragment->id,
            (string) $revision->id,
            $deps,
        );
    }

    public function indexPageRevision(Page $page, PageRevision $revision): void
    {
        $content = is_array($revision->content) ? $revision->content : [];
        $deps = $this->extractFromDocument($content);
        if (! empty($page->templateId) || ! empty($content['templateId'])) {
            $deps[] = [
                'type' => 'template',
                'id' => (string) ($page->templateId ?? $content['templateId']),
                'mode' => 'linked',
            ];
        }
        $this->replaceFor(
            (string) $page->siteId,
            'page',
            (string) $page->id,
            (string) $revision->id,
            $deps,
        );
    }
}
