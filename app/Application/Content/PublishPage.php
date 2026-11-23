<?php

namespace App\Application\Content;

use App\Application\Composition\ContentComposer;
use App\Application\Composition\ContentDependencyGraph;
use App\Application\Composition\RenderContext;
use App\Domain\Content\Page;
use App\Domain\Content\PageRevision;
use App\Domain\Content\PublishedRelease;
use Illuminate\Support\Str;

final class PublishPage
{
    public function __construct(
        private readonly ContentComposer $composer = new ContentComposer,
        private readonly ContentDependencyGraph $graph = new ContentDependencyGraph,
    ) {}

    /**
     * @return array{page: Page, revision: PageRevision, release: PublishedRelease}
     */
    public function handle(string $pageId, ?string $message = null): array
    {
        $page = Page::query()->find($pageId);
        if (! $page) {
            throw new \RuntimeException('Page not found.', 404);
        }

        $revision = PageRevision::query()->find($page->draftRevisionId);
        if (! $revision) {
            throw new \RuntimeException('Draft revision missing.', 422);
        }

        $context = new RenderContext(mode: 'published', siteId: (string) $page->siteId);
        $composed = $this->composer->composePage($page, $revision, $context);

        $fragmentRevisions = [];
        foreach ($context->dependencies as $dep) {
            if (($dep['type'] ?? '') === 'fragment' && ! empty($dep['revisionId'])) {
                $fragmentRevisions[$dep['id']] = [
                    'revisionId' => $dep['revisionId'],
                    'variation' => $dep['variation'] ?? 'default',
                ];
            }
        }

        $releaseId = (string) Str::ulid();
        $release = PublishedRelease::query()->create([
            '_id' => $releaseId,
            'siteId' => $page->siteId,
            'pageId' => $page->id,
            'pageRevisionId' => $revision->id,
            'templateId' => $composed['templateId'],
            'templateRevisionId' => $composed['templateRevisionId'],
            'fragmentRevisions' => $fragmentRevisions,
            'resolvedContent' => $composed['content'],
            'status' => 'active',
            'message' => $message ?? 'Publish page',
            'activatedAt' => now()->toIso8601String(),
        ]);

        $page->publishedRevisionId = $revision->id;
        $page->publishedReleaseId = $releaseId;
        $page->status = 'published';
        $page->save();

        $this->graph->indexPageRevision($page, $revision);

        return [
            'page' => $page->fresh(),
            'revision' => $revision,
            'release' => $release,
        ];
    }
}
