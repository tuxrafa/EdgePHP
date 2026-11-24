<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Content\CreatePage;
use App\Application\Content\GetPage;
use App\Application\Content\ListPageChildren;
use App\Application\Content\ListPages;
use App\Application\Content\PublishPage;
use App\Application\Content\UpdatePage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request, ListPages $listPages): JsonResponse
    {
        $siteId = (string) $request->query('siteId', 'main');
        $locale = $request->query('locale');
        $pages = $listPages->handle($siteId, is_string($locale) ? $locale : null);

            return response()->json([
                'data' => $pages->map(fn ($page) => [
                'id' => $page->id,
                'siteId' => $page->siteId,
                'locale' => $page->locale,
                'path' => $page->path,
                'title' => $page->title,
                'status' => $page->status,
                'templateId' => $page->templateId,
                'draftRevisionId' => $page->draftRevisionId,
                'publishedRevisionId' => $page->publishedRevisionId,
            ])->values(),
        ]);
    }

    public function children(Request $request, ListPageChildren $listPageChildren): JsonResponse
    {
        $siteId = (string) $request->query('siteId', 'main');
        $locale = $request->query('locale');
        $parent = (string) $request->query('parent', '');
        $limit = (int) $request->query('limit', 200);
        $offset = (int) $request->query('offset', 0);

        $result = $listPageChildren->handle(
            $siteId,
            is_string($locale) ? $locale : null,
            $parent,
            $limit,
            $offset,
        );

        return response()->json($result);
    }

    public function store(Request $request, CreatePage $createPage): JsonResponse
    {
        $validated = $request->validate([
            'siteId' => ['sometimes', 'string', 'max:64'],
            'locale' => ['sometimes', 'string', 'max:16'],
            'path' => ['required', 'string', 'max:512'],
            'title' => ['sometimes', 'string', 'max:512'],
            'templateId' => ['sometimes', 'nullable', 'string', 'max:64'],
            'metadata' => ['sometimes', 'array'],
            'sections' => ['sometimes', 'array'],
            'content' => ['sometimes', 'array'],
            'message' => ['sometimes', 'nullable', 'string', 'max:512'],
        ]);

        try {
            $result = $createPage->handle($validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($result['page'], $result['revision'])], 201);
    }

    public function show(string $id, GetPage $getPage): JsonResponse
    {
        try {
            $result = $getPage->byId($id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }

        return response()->json([
            'data' => $this->serialize($result['page'], $result['revision']),
        ]);
    }

    public function update(string $id, Request $request, UpdatePage $updatePage): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:512'],
            'path' => ['sometimes', 'string', 'max:512'],
            'templateId' => ['sometimes', 'nullable', 'string', 'max:64'],
            'metadata' => ['sometimes', 'array'],
            'content' => ['sometimes', 'array'],
            'expectedRevisionId' => ['sometimes', 'nullable', 'string'],
            'message' => ['sometimes', 'nullable', 'string', 'max:512'],
        ]);

        try {
            $result = $updatePage->handle($id, $validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }

        return response()->json([
            'data' => $this->serialize($result['page'], $result['revision']),
        ]);
    }

    public function publish(string $id, Request $request, PublishPage $publishPage): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['sometimes', 'nullable', 'string', 'max:512'],
        ]);

        try {
            $result = $publishPage->handle($id, $validated['message'] ?? null);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }

        return response()->json([
            'data' => [
                ...$this->serialize($result['page'], $result['revision']),
                'releaseId' => $result['release']->id,
            ],
        ]);
    }

    private function serialize($page, $revision): array
    {
        return [
            'id' => $page->id,
            'siteId' => $page->siteId,
            'locale' => $page->locale,
            'path' => $page->path,
            'title' => $page->title,
            'status' => $page->status,
            'templateId' => $page->templateId,
            'draftRevisionId' => $page->draftRevisionId,
            'publishedRevisionId' => $page->publishedRevisionId,
            'publishedReleaseId' => $page->publishedReleaseId ?? null,
            'metadata' => $page->metadata,
            'revision' => $revision ? [
                'id' => $revision->id,
                'number' => $revision->number,
                'content' => $revision->content,
            ] : null,
        ];
    }
}
