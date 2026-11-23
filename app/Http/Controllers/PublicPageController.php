<?php

namespace App\Http\Controllers;

use App\Application\Composition\ContentComposer;
use App\Application\Composition\RenderContext;
use App\Application\Content\GetPage;
use App\Compatibility\Eds\EdsPageRenderer;
use App\Domain\Content\PublishedRelease;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicPageController extends Controller
{
    public function show(
        string $path,
        Request $request,
        GetPage $getPage,
        EdsPageRenderer $renderer,
        ContentComposer $composer,
    ): Response {
        $siteId = (string) $request->query('site', 'main');
        $locale = (string) $request->query('locale', 'pt-BR');

        try {
            $result = $getPage->byPath($path, $siteId, $locale);
        } catch (\RuntimeException) {
            abort(404);
        }

        $page = $result['page'];
        $revision = $result['revision'];

        // Prefer immutable published release when available.
        if ($page->publishedReleaseId && $page->publishedRevisionId === $revision->id) {
            $release = PublishedRelease::query()->find($page->publishedReleaseId);
            if ($release && is_array($release->resolvedContent)) {
                $rendered = $renderer->renderContent($release->resolvedContent);

                return response($rendered->html, 200)
                    ->header('Content-Type', 'text/html; charset=UTF-8');
            }
        }

        $mode = $page->publishedRevisionId && $page->publishedRevisionId === $revision->id
            ? 'published'
            : 'preview';

        $context = new RenderContext(mode: $mode, siteId: $siteId);

        try {
            $composed = $composer->composePage($page, $revision, $context);
        } catch (\RuntimeException) {
            abort(404);
        }

        $rendered = $renderer->renderContent($composed['content']);

        return response($rendered->html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
