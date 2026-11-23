<?php

namespace App\Http\Controllers;

use App\Application\Composition\ContentComposer;
use App\Application\Composition\RenderContext;
use App\Compatibility\Eds\EdsPageRenderer;
use App\Domain\Content\Page;
use App\Domain\Content\PageRevision;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PreviewController extends Controller
{
    public function show(
        string $id,
        Request $request,
        EdsPageRenderer $renderer,
        ContentComposer $composer,
    ): Response {
        $page = Page::query()->find($id);
        if (! $page) {
            abort(404);
        }

        $revisionId = $request->query('revision') ?: $page->draftRevisionId;
        $revision = PageRevision::query()->find($revisionId);
        if (! $revision || $revision->pageId !== $page->id) {
            abort(404);
        }

        $context = new RenderContext(
            mode: 'preview',
            siteId: (string) $page->siteId,
        );

        try {
            $composed = $composer->composePage($page, $revision, $context);
        } catch (\RuntimeException $e) {
            abort(response($e->getMessage(), 422));
        }

        $rendered = $renderer->renderContent($composed['content']);

        return response($rendered->html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex')
            ->header('X-EdgePHP-Preview', '1')
            ->header(
                'X-EdgePHP-Composition',
                $composed['templateId'] ? 'template' : 'legacy'
            );
    }
}
