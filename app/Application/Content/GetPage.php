<?php

namespace App\Application\Content;

use App\Domain\Content\Page;
use App\Domain\Content\PageRevision;

final class GetPage
{
    /**
     * @return array{page: Page, revision: PageRevision|null}
     */
    public function byId(string $id): array
    {
        $page = Page::query()->find($id);
        if (! $page) {
            throw new \RuntimeException('Page not found.', 404);
        }

        $revision = null;
        if ($page->draftRevisionId) {
            $revision = PageRevision::query()->find($page->draftRevisionId);
        }

        return ['page' => $page, 'revision' => $revision];
    }

    /**
     * @return array{page: Page, revision: PageRevision}
     */
    public function byPath(string $path, string $siteId = 'main', string $locale = 'pt-BR'): array
    {
        $path = '/'.ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $page = Page::query()
            ->where('siteId', $siteId)
            ->where('locale', $locale)
            ->where('path', $path)
            ->first();

        if (! $page) {
            throw new \RuntimeException('Page not found.', 404);
        }

        $revisionId = $page->publishedRevisionId ?: $page->draftRevisionId;
        $revision = PageRevision::query()->find($revisionId);
        if (! $revision) {
            throw new \RuntimeException('Page revision not found.', 404);
        }

        return ['page' => $page, 'revision' => $revision];
    }
}
